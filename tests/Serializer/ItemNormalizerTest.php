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

namespace ApiPlatform\Tests\Serializer;

use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Serializer\ItemNormalizer;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\OutputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @group legacy
 */
class ItemNormalizerTest extends TestCase
{
    use ExpectDeprecationTrait;
    use ProphecyTrait;

    /**
     * @group legacy
     */
    public function testSupportNormalization()
    {
        $std = new \stdClass();
        $dummy = new Dummy();
        $dummy->setDescription('hello');

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(\stdClass::class)->willReturn(false);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal()
        );

        $this->assertTrue($normalizer->supportsNormalization($dummy));
        $this->assertTrue($normalizer->supportsNormalization($dummy));
        $this->assertFalse($normalizer->supportsNormalization($std));

        $this->assertTrue($normalizer->supportsDenormalization($dummy, Dummy::class));
        $this->assertTrue($normalizer->supportsDenormalization($dummy, Dummy::class));
        $this->assertFalse($normalizer->supportsDenormalization($std, \stdClass::class));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    public function testNormalize()
    {
        $dummy = new Dummy();
        $dummy->setName('hello');

        $propertyNameCollection = new PropertyNameCollection(['name']);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn($propertyNameCollection);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $propertyMetadata = (new ApiProperty())->withReadable(true);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn($propertyMetadata);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->willReturn('/dummies/1');

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize('hello', null, Argument::type('array'))->willReturn('hello');

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            null,
            null,
            null,
            false,
            null,
            [],
            null
        );
        $normalizer->setSerializer($serializerProphecy->reveal());

        $this->assertEquals(['name' => 'hello'], $normalizer->normalize($dummy, null, ['resources' => []]));
    }

    public function testDenormalize()
    {
        $context = ['resource_class' => Dummy::class, 'api_allow_update' => true];

        $propertyNameCollection = new PropertyNameCollection(['name']);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn($propertyNameCollection)->shouldBeCalled();

        $propertyMetadata = (new ApiProperty())->withReadable(true)->withWritable(true);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn($propertyMetadata)->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            null,
            null,
            null,
            false,
            null,
            [],
            null
        );
        $normalizer->setSerializer($serializerProphecy->reveal());

        $this->assertInstanceOf(Dummy::class, $normalizer->denormalize(['name' => 'hello'], Dummy::class, null, $context));
    }

    public function testDenormalizeWithIri()
    {
        $context = ['resource_class' => Dummy::class, 'api_allow_update' => true];

        $propertyNameCollection = new PropertyNameCollection(['id', 'name']);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn($propertyNameCollection)->shouldBeCalled();

        $propertyMetadata = (new ApiProperty())->withReadable(true)->withWritable(true);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'id', [])->willReturn($propertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn($propertyMetadata)->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getItemFromIri('/dummies/12', ['resource_class' => Dummy::class, 'api_allow_update' => true, 'fetch_data' => true])->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            null,
            null,
            null,
            false,
            null,
            [],
            null
        );
        $normalizer->setSerializer($serializerProphecy->reveal());

        $this->assertInstanceOf(Dummy::class, $normalizer->denormalize(['id' => '/dummies/12', 'name' => 'hello'], Dummy::class, null, $context));
    }

    public function testDenormalizeWithIdAndUpdateNotAllowed()
    {
        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessage('Update is not allowed for this operation.');

        $context = ['resource_class' => Dummy::class, 'api_allow_update' => false];

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            null,
            null,
            null,
            false,
            null,
            [],
            null
        );
        $normalizer->setSerializer($serializerProphecy->reveal());
        $normalizer->denormalize(['id' => '12', 'name' => 'hello'], Dummy::class, null, $context);
    }

    public function testDenormalizeWithDefinedIri()
    {
        $dummy = new Dummy();
        $dummy->setName('hello');

        $propertyNameCollection = new PropertyNameCollection(['name']);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn($propertyNameCollection);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $propertyMetadata = (new ApiProperty())->withReadable(true);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn($propertyMetadata);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->shouldNotBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize('hello', null, Argument::type('array'))->willReturn('hello');

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            null,
            null,
            null,
            false,
            null,
            [],
            null
        );
        $normalizer->setSerializer($serializerProphecy->reveal());

        $this->assertEquals(['name' => 'hello'], $normalizer->normalize($dummy, null, ['resources' => [], 'iri' => '/custom']));
    }

    public function testDenormalizeWithIdAndNoResourceClass()
    {
        $context = [];

        $propertyNameCollection = new PropertyNameCollection(['id', 'name']);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn($propertyNameCollection)->shouldBeCalled();

        $propertyMetadata = (new ApiProperty())->withReadable(true)->withWritable(true);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'id', [])->willReturn($propertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn($propertyMetadata)->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(null, Dummy::class)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(DenormalizerInterface::class);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            null,
            null,
            null,
            false,
            null,
            [],
            null
        );
        $normalizer->setSerializer($serializerProphecy->reveal());

        $object = $normalizer->denormalize(['id' => '42', 'name' => 'hello'], Dummy::class, null, $context);
        $this->assertInstanceOf(Dummy::class, $object);
        $this->assertSame('42', $object->getId());
        $this->assertSame('hello', $object->getName());
    }

    /**
     * @group legacy
     */
    public function testNormalizeWithDataTransformers()
    {
        $this->expectDeprecation('Since api-platform/core 2.7: The DataTransformer pattern is deprecated, use a Provider or a Processor and either use your input or return a new output there.');
        $dummy = new Dummy();
        $dummy->setName('hello');
        $output = new OutputDto();

        $propertyNameCollection = new PropertyNameCollection(['baz']);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadata = new ApiProperty(null, null, true);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($output, null)->willThrow(InvalidArgumentException::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize(Argument::type(OutputDto::class), null, Argument::type('array'))->willReturn(['baz' => 'hello'])->shouldBeCalled();

        $dataTransformer = $this->prophesize(DataTransformerInterface::class);
        $dataTransformer->supportsTransformation($dummy, OutputDto::class, Argument::any())->shouldBeCalled()->willReturn(true);
        $dataTransformer->transform($dummy, OutputDto::class, Argument::any())->shouldBeCalled()->willReturn($output);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            null,
            null,
            null,
            null,
            false,
            null,
            [$dataTransformer->reveal()],
            null
        );
        $normalizer->setSerializer($serializerProphecy->reveal());

        $this->assertEquals(['baz' => 'hello'], $normalizer->normalize($dummy, null, ['resources' => [], 'output' => ['class' => OutputDto::class]]));
    }
}
