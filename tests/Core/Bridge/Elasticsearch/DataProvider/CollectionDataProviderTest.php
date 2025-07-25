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
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\CollectionDataProvider;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\RequestBodySearchCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Paginator;
use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\Pagination;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Elasticsearch\Exception\IndexNotFoundException;
use ApiPlatform\Elasticsearch\Exception\NonUniqueIdentifierException;
use ApiPlatform\Elasticsearch\Metadata\Document\DocumentMetadata;
use ApiPlatform\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Exception\ResourceClassNotFoundException;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CompositeRelation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCarColor;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Foo;
use Elasticsearch\Client;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @group legacy
 *
 * @requires PHP >= 7.4
 */
class CollectionDataProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct()
    {
        self::assertInstanceOf(
            CollectionDataProviderInterface::class,
            new CollectionDataProvider(
                $this->prophesize(Client::class)->reveal(),
                $this->prophesize(DocumentMetadataFactoryInterface::class)->reveal(),
                $this->prophesize(IdentifierExtractorInterface::class)->reveal(),
                $this->prophesize(DenormalizerInterface::class)->reveal(),
                new Pagination($this->prophesize(ResourceMetadataFactoryInterface::class)->reveal()),
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
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->shouldBeCalled()->willReturn((new ResourceMetadata())->withAttributes(['elasticsearch' => false]));
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadata());
        $resourceMetadataFactoryProphecy->create(CompositeRelation::class)->shouldBeCalled()->willReturn(new ResourceMetadata());
        $resourceMetadataFactoryProphecy->create(DummyCarColor::class)->shouldBeCalled()->willThrow(new ResourceClassNotFoundException());

        $collectionDataProvider = new CollectionDataProvider(
            $this->prophesize(Client::class)->reveal(),
            $documentMetadataFactoryProphecy->reveal(),
            $identifierExtractorProphecy->reveal(),
            $this->prophesize(DenormalizerInterface::class)->reveal(),
            new Pagination($this->prophesize(ResourceMetadataFactoryInterface::class)->reveal()),
            $resourceMetadataFactoryProphecy->reveal()
        );

        self::assertTrue($collectionDataProvider->supports(Foo::class));
        self::assertFalse($collectionDataProvider->supports(Dummy::class));
        self::assertFalse($collectionDataProvider->supports(CompositeRelation::class));
        self::assertFalse($collectionDataProvider->supports(DummyCar::class));
        self::assertFalse($collectionDataProvider->supports(DummyCarColor::class));
    }

    public function testGetCollection()
    {
        $context = [
            'groups' => ['custom'],
        ];

        $documentMetadataFactoryProphecy = $this->prophesize(DocumentMetadataFactoryInterface::class);
        $documentMetadataFactoryProphecy->create(Foo::class)->willReturn(new DocumentMetadata('foo'))->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Foo::class)->willReturn(new ResourceMetadata());

        $documents = [
            'took' => 15,
            'time_out' => false,
            '_shards' => [
                'total' => 5,
                'successful' => 5,
                'skipped' => 0,
                'failed' => 0,
            ],
            'hits' => [
                'total' => 4,
                'max_score' => 1,
                'hits' => [
                    [
                        '_index' => 'foo',
                        '_type' => '_doc',
                        '_id' => '1',
                        '_score' => 1,
                        '_source' => [
                            'id' => 1,
                            'name' => 'Kilian',
                            'bar' => 'Jornet',
                        ],
                    ],
                    [
                        '_index' => 'foo',
                        '_type' => '_doc',
                        '_id' => '2',
                        '_score' => 1,
                        '_source' => [
                            'id' => 2,
                            'name' => 'François',
                            'bar' => 'D\'Haene',
                        ],
                    ],
                ],
            ],
        ];

        $clientProphecy = $this->prophesize(Client::class);
        $clientProphecy
            ->search(
                Argument::allOf(
                    Argument::withEntry('index', 'foo'),
                    Argument::withEntry('body', Argument::allOf(
                        Argument::withEntry('size', 2),
                        Argument::withEntry('from', 0),
                        Argument::withEntry('query', Argument::allOf(
                            Argument::withEntry('match_all', Argument::type(\stdClass::class)),
                            Argument::size(1)
                        )),
                        Argument::size(3)
                    )),
                    Argument::size(2)
                )
            )
            ->willReturn($documents)
            ->shouldBeCalled();

        $requestBodySearchCollectionExtensionProphecy = $this->prophesize(RequestBodySearchCollectionExtensionInterface::class);
        $requestBodySearchCollectionExtensionProphecy->applyToCollection([], Foo::class, 'get', $context)->willReturn([])->shouldBeCalled();

        $collectionDataProvider = new CollectionDataProvider(
            $clientProphecy->reveal(),
            $documentMetadataFactoryProphecy->reveal(),
            $this->prophesize(IdentifierExtractorInterface::class)->reveal(),
            $denormalizer = $this->prophesize(DenormalizerInterface::class)->reveal(),
            new Pagination($resourceMetadataFactoryProphecy->reveal(), ['items_per_page' => 2]),
            $resourceMetadataFactoryProphecy->reveal(),
            [$requestBodySearchCollectionExtensionProphecy->reveal()]
        );

        self::assertEquals(
            new Paginator($denormalizer, $documents, Foo::class, 2, 0, $context),
            $collectionDataProvider->getCollection(Foo::class, 'get', $context)
        );
    }
}
