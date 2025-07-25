<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Tests\Bridge\Elasticsearch\DataProvider;

use ApiPlatform\Core\Bridge\Elasticsearch\Api\IdentifierExtractorInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\ItemDataProvider;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Elasticsearch\Exception\IndexNotFoundException;
use ApiPlatform\Elasticsearch\Exception\NonUniqueIdentifierException;
use ApiPlatform\Elasticsearch\Metadata\Document\DocumentMetadata;
use ApiPlatform\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Elasticsearch\Serializer\DocumentNormalizer;
use ApiPlatform\Exception\ResourceClassNotFoundException;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CompositeRelation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCarColor;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Foo;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @group legacy
 *
 * @requires PHP >= 7.4
 */
class ItemDataProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct()
    {
        self::assertInstanceOf(
            ItemDataProviderInterface::class,
            new ItemDataProvider(
                $this->prophesize(Client::class)->reveal(),
                $this->prophesize(DocumentMetadataFactoryInterface::class)->reveal(),
                $this->prophesize(IdentifierExtractorInterface::class)->reveal(),
                $this->prophesize(DenormalizerInterface::class)->reveal(),
                $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal()
            )
        );
    }

    public function testSupports()
    {
        $documentMetadataFactoryProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $documentMetadataFactoryProphecy->create(Foo::class)->willReturn(new DocumentMetadata('foo'))->shouldBeCalled();
        $documentMetadataFactoryProphecy->create(Dummy::class)->willThrow(new IndexNotFoundException())->shouldBeCalled();
        $documentMetadataFactoryProphecy->create(CompositeRelation::class)->willReturn(new DocumentMetadata('composite_relation'))->shouldBeCalled();

        $identifierExtractorProphecy = $this->prophesize(IdentifierExtractorInterface::class);
        $identifierExtractorProphecy->getIdentifierFromResourceClass(Foo::class)->willReturn('id')->shouldBeCalled();
        $identifierExtractorProphecy->getIdentifierFromResourceClass(CompositeRelation::class)->willThrow(new NonUniqueIdentifierException())->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Foo::class)->shouldBeCalled()->willReturn(new ResourceMetadata());
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadata());
        $resourceMetadataFactoryProphecy->create(CompositeRelation::class)->shouldBeCalled()->willReturn(new ResourceMetadata());
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->shouldBeCalled()->willReturn((new ResourceMetadata())->withAttributes(['elasticsearch' => false]));
        $resourceMetadataFactoryProphecy->create(DummyCarColor::class)->shouldBeCalled()->willThrow(new ResourceClassNotFoundException());

        $itemDataProvider = new ItemDataProvider(
            $this->prophesize(Client::class)->reveal(),
            $documentMetadataFactoryProphecy->reveal(),
            $identifierExtractorProphecy->reveal(),
            $this->prophesize(DenormalizerInterface::class)->reveal(),
            $resourceMetadataFactoryProphecy->reveal()
        );

        self::assertTrue($itemDataProvider->supports(Foo::class));
        self::assertFalse($itemDataProvider->supports(Dummy::class));
        self::assertFalse($itemDataProvider->supports(CompositeRelation::class));
        self::assertFalse($itemDataProvider->supports(DummyCar::class));
        self::assertFalse($itemDataProvider->supports(DummyCarColor::class));
    }

    public function testGetItem()
    {
        $documentMetadataFactoryProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $documentMetadataFactoryProphecy->create(Foo::class)->willReturn(new DocumentMetadata('foo'))->shouldBeCalled();

        $identifierExtractorProphecy = $this->prophesize(IdentifierExtractorInterface::class);
        $identifierExtractorProphecy->getIdentifierFromResourceClass(Foo::class)->willReturn('id')->shouldBeCalled();

        $document = [
            '_index' => 'foo',
            '_type' => '_doc',
            '_id' => '1',
            '_version' => 1,
            'found' => true,
            '_source' => [
                'id' => 1,
                'name' => 'Rossinière',
                'bar' => 'erèinissor',
            ],
        ];

        $foo = new Foo();
        $foo->setName('Rossinière');
        $foo->setBar('erèinissor');

        $clientProphecy = $this->prophesize(Client::class);
        $clientProphecy->get(['index' => 'foo', 'id' => '1'])->willReturn($document)->shouldBeCalled();

        $denormalizerProphecy = $this->prophesize(DenormalizerInterface::class);
        $denormalizerProphecy->denormalize($document, Foo::class, DocumentNormalizer::FORMAT, [AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true])->willReturn($foo)->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $itemDataProvider = new ItemDataProvider($clientProphecy->reveal(), $documentMetadataFactoryProphecy->reveal(), $identifierExtractorProphecy->reveal(), $denormalizerProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal());

        self::assertSame($foo, $itemDataProvider->getItem(Foo::class, ['id' => 1]));
    }

    public function testGetItemWithMissing404Exception()
    {
        $documentMetadataFactoryProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $documentMetadataFactoryProphecy->create(Foo::class)->willReturn(new DocumentMetadata('foo'))->shouldBeCalled();

        $identifierExtractorProphecy = $this->prophesize(IdentifierExtractorInterface::class);
        $identifierExtractorProphecy->getIdentifierFromResourceClass(Foo::class)->willReturn('id')->shouldBeCalled();

        $clientProphecy = $this->prophesize(Client::class);
        $clientProphecy->get(['index' => 'foo', 'id' => '404'])->willThrow(new Missing404Exception())->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $itemDataProvider = new ItemDataProvider($clientProphecy->reveal(), $documentMetadataFactoryProphecy->reveal(), $identifierExtractorProphecy->reveal(), $this->prophesize(DenormalizerInterface::class)->reveal(), $resourceMetadataFactoryProphecy->reveal());

        self::assertNull($itemDataProvider->getItem(Foo::class, ['id' => 404]));
    }
}
