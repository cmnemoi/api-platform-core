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

namespace ApiPlatform\Core\Tests\Metadata\Property\Factory;

use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\SerializerPropertyMetadataFactory;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyTableInheritance;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyTableInheritanceChild;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Mapping\AttributeMetadata as SerializerAttributeMetadata;
use Symfony\Component\Serializer\Mapping\ClassMetadata as SerializerClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface as SerializerClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @group legacy
 */
class SerializerPropertyMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $serializerClassMetadataFactoryProphecy = $this->prophesize(SerializerClassMetadataFactoryInterface::class);
        $serializerClassMetadataFactory = $serializerClassMetadataFactoryProphecy->reveal();

        $decoratedProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decorated = $decoratedProphecy->reveal();

        $serializerPropertyMetadataFactory = new SerializerPropertyMetadataFactory($resourceMetadataFactory, $serializerClassMetadataFactory, $decorated);

        $this->assertInstanceOf(PropertyMetadataFactoryInterface::class, $serializerPropertyMetadataFactory);
    }

    /**
     * @dataProvider groupsProvider
     */
    public function testCreateLegacy($readGroups, $writeGroups, string $relatedOutputClass = null)
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $dummyResourceMetadata = (new ResourceMetadata())
            ->withAttributes([
                'normalization_context' => [
                    AbstractNormalizer::GROUPS => $readGroups,
                ],
                'denormalization_context' => [
                    AbstractNormalizer::GROUPS => $writeGroups,
                ],
            ]);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($dummyResourceMetadata);
        $relatedDummyResourceMetadata = new ResourceMetadata();
        if ($relatedOutputClass) {
            $relatedDummyResourceMetadata = $relatedDummyResourceMetadata->withAttributes([
                'output' => ['class' => $relatedOutputClass],
            ]);
        }
        $resourceMetadataFactoryProphecy->create(RelatedDummy::class)->willReturn($relatedDummyResourceMetadata);

        $serializerClassMetadataFactoryProphecy = $this->prophesize(SerializerClassMetadataFactoryInterface::class);
        $dummySerializerClassMetadata = new SerializerClassMetadata(Dummy::class);
        $fooSerializerAttributeMetadata = new SerializerAttributeMetadata('foo');
        $fooSerializerAttributeMetadata->addGroup('dummy_read');
        $fooSerializerAttributeMetadata->addGroup('dummy_write');
        $dummySerializerClassMetadata->addAttributeMetadata($fooSerializerAttributeMetadata);
        $relatedDummySerializerAttributeMetadata = new SerializerAttributeMetadata('relatedDummy');
        $relatedDummySerializerAttributeMetadata->addGroup('dummy_read');
        $relatedDummySerializerAttributeMetadata->addGroup('dummy_write');
        $dummySerializerClassMetadata->addAttributeMetadata($relatedDummySerializerAttributeMetadata);
        $nameConvertedSerializerAttributeMetadata = new SerializerAttributeMetadata('nameConverted');
        $dummySerializerClassMetadata->addAttributeMetadata($nameConvertedSerializerAttributeMetadata);
        $serializerClassMetadataFactoryProphecy->getMetadataFor(Dummy::class)->willReturn($dummySerializerClassMetadata);
        $relatedDummySerializerClassMetadata = new SerializerClassMetadata(RelatedDummy::class);
        $nameSerializerAttributeMetadata = new SerializerAttributeMetadata('name');
        $nameSerializerAttributeMetadata->addGroup('dummy_read');
        $relatedDummySerializerClassMetadata->addAttributeMetadata($nameSerializerAttributeMetadata);
        $serializerClassMetadataFactoryProphecy->getMetadataFor(RelatedDummy::class)->willReturn($relatedDummySerializerClassMetadata);
        $dummyCarSerializerClassMetadata = new SerializerClassMetadata(DummyCar::class);
        $nameSerializerAttributeMetadata = new SerializerAttributeMetadata('name');
        $nameSerializerAttributeMetadata->addGroup('dummy_car_read');
        $nameSerializerAttributeMetadata->addGroup('dummy_write');
        $dummyCarSerializerClassMetadata->addAttributeMetadata($nameSerializerAttributeMetadata);
        $serializerClassMetadataFactoryProphecy->getMetadataFor(DummyCar::class)->willReturn($dummyCarSerializerClassMetadata);

        $decoratedProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $fooPropertyMetadata = (new PropertyMetadata())
            ->withType(new Type(Type::BUILTIN_TYPE_ARRAY, true))
            ->withReadable(false)
            ->withWritable(true);
        $decoratedProphecy->create(Dummy::class, 'foo', [])->willReturn($fooPropertyMetadata);
        $relatedDummyPropertyMetadata = (new PropertyMetadata())
            ->withType(new Type(Type::BUILTIN_TYPE_OBJECT, true, RelatedDummy::class));
        $decoratedProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn($relatedDummyPropertyMetadata);
        $nameConvertedPropertyMetadata = (new PropertyMetadata())
            ->withType(new Type(Type::BUILTIN_TYPE_STRING, true));
        $decoratedProphecy->create(Dummy::class, 'nameConverted', [])->willReturn($nameConvertedPropertyMetadata);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);
        $resourceClassResolverProphecy->getResourceClass(null, RelatedDummy::class)->willReturn(RelatedDummy::class);

        $serializerPropertyMetadataFactory = new SerializerPropertyMetadataFactory($resourceMetadataFactoryProphecy->reveal(), $serializerClassMetadataFactoryProphecy->reveal(), $decoratedProphecy->reveal(), $resourceClassResolverProphecy->reveal());

        $actual = [];
        $actual[] = $serializerPropertyMetadataFactory->create(Dummy::class, 'foo');
        $actual[] = $serializerPropertyMetadataFactory->create(Dummy::class, 'relatedDummy');
        $actual[] = $serializerPropertyMetadataFactory->create(Dummy::class, 'nameConverted');

        $this->assertInstanceOf(PropertyMetadata::class, $actual[0]);
        $this->assertFalse($actual[0]->isReadable());
        $this->assertTrue($actual[0]->isWritable());

        $this->assertInstanceOf(PropertyMetadata::class, $actual[1]);
        $this->assertTrue($actual[1]->isReadable());
        $this->assertTrue($actual[1]->isWritable());
        if ($relatedOutputClass) {
            $this->assertFalse($actual[1]->isReadableLink());
            $this->assertTrue($actual[1]->isWritableLink());
        } else {
            $this->assertTrue($actual[1]->isReadableLink());
            $this->assertFalse($actual[1]->isWritableLink());
        }

        $this->assertInstanceOf(PropertyMetadata::class, $actual[2]);
        $this->assertFalse($actual[2]->isReadable());
        $this->assertFalse($actual[2]->isWritable());
    }

    public function groupsProvider(): array
    {
        return [
            [['dummy_read'], ['dummy_write']],
            ['dummy_read', 'dummy_write'],
            ['dummy_read', 'dummy_write', DummyCar::class],
        ];
    }

    /**
     * @group legacy
     */
    public function testCreateInherited(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyTableInheritanceChild::class)->willReturn(new ResourceMetadata());

        $serializerClassMetadataFactoryProphecy = $this->prophesize(SerializerClassMetadataFactoryInterface::class);
        $dummySerializerClassMetadata = new SerializerClassMetadata(DummyTableInheritanceChild::class);
        $serializerClassMetadataFactoryProphecy->getMetadataFor(DummyTableInheritanceChild::class)->willReturn($dummySerializerClassMetadata);

        $decoratedProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $fooPropertyMetadata = (new PropertyMetadata())
            ->withType(new Type(Type::BUILTIN_TYPE_ARRAY, true))
            ->withChildInherited(DummyTableInheritanceChild::class);
        $decoratedProphecy->create(DummyTableInheritance::class, 'nickname', [])->willReturn($fooPropertyMetadata);

        $serializerPropertyMetadataFactory = new SerializerPropertyMetadataFactory($resourceMetadataFactoryProphecy->reveal(), $serializerClassMetadataFactoryProphecy->reveal(), $decoratedProphecy->reveal());

        $actual = $serializerPropertyMetadataFactory->create(DummyTableInheritance::class, 'nickname');

        $this->assertEquals($actual->getChildInherited(), DummyTableInheritanceChild::class);
    }
}
