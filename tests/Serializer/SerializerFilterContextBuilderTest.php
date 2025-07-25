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

use ApiPlatform\Api\FilterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Serializer\Filter\FilterInterface as SerializerFilterInterface;
use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Serializer\SerializerFilterContextBuilder;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyGroup;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 *
 * @group legacy
 */
class SerializerFilterContextBuilderTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateFromRequestWithCollectionOperation()
    {
        $request = new Request();

        $attributes = [
            'resource_class' => DummyGroup::class,
            'collection_operation_name' => 'get',
        ];

        $resourceMetadata = new ResourceMetadata(
            null,
            null,
            null,
            null,
            ['get' => ['filters' => ['dummy_group.group', 'dummy_group.search', 'dummy_group.nonexistent']]]
        );

        $decoratedProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $decoratedProphecy->createFromRequest($request, true, $attributes)->willReturn([])->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyGroup::class)->willReturn($resourceMetadata)->shouldBeCalled();

        $dummyGroupGroupFilterProphecy = $this->prophesize(SerializerFilterInterface::class);
        $dummyGroupGroupFilterProphecy->apply($request, true, $attributes, [])->shouldBeCalled();

        $dummyGroupSearchFilterProphecy = $this->prophesize(FilterInterface::class);

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filterLocatorProphecy->has('dummy_group.group')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('dummy_group.group')->willReturn($dummyGroupGroupFilterProphecy->reveal())->shouldBeCalled();
        $filterLocatorProphecy->has('dummy_group.search')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('dummy_group.search')->willReturn($dummyGroupSearchFilterProphecy->reveal())->shouldBeCalled();
        $filterLocatorProphecy->has('dummy_group.nonexistent')->willReturn(false)->shouldBeCalled();

        $serializerContextBuilderFilter = new SerializerFilterContextBuilder($resourceMetadataFactoryProphecy->reveal(), $filterLocatorProphecy->reveal(), $decoratedProphecy->reveal());
        $serializerContextBuilderFilter->createFromRequest($request, true, $attributes);
    }

    public function testCreateFromRequestWithItemOperation()
    {
        $request = new Request();

        $attributes = [
            'resource_class' => DummyGroup::class,
            'item_operation_name' => 'put',
        ];

        $resourceMetadata = new ResourceMetadata(
            null,
            null,
            null,
            ['put' => ['filters' => ['dummy_group.group', 'dummy_group.search', 'dummy_group.nonexistent']]],
            null
        );

        $decoratedProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $decoratedProphecy->createFromRequest($request, true, $attributes)->willReturn([])->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyGroup::class)->willReturn($resourceMetadata)->shouldBeCalled();

        $dummyGroupGroupFilterProphecy = $this->prophesize(SerializerFilterInterface::class);
        $dummyGroupGroupFilterProphecy->apply($request, true, $attributes, [])->shouldBeCalled();

        $dummyGroupSearchFilterProphecy = $this->prophesize(FilterInterface::class);

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filterLocatorProphecy->has('dummy_group.group')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('dummy_group.group')->willReturn($dummyGroupGroupFilterProphecy->reveal())->shouldBeCalled();
        $filterLocatorProphecy->has('dummy_group.search')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('dummy_group.search')->willReturn($dummyGroupSearchFilterProphecy->reveal())->shouldBeCalled();
        $filterLocatorProphecy->has('dummy_group.nonexistent')->willReturn(false)->shouldBeCalled();

        $serializerContextBuilderFilter = new SerializerFilterContextBuilder($resourceMetadataFactoryProphecy->reveal(), $filterLocatorProphecy->reveal(), $decoratedProphecy->reveal());
        $serializerContextBuilderFilter->createFromRequest($request, true, $attributes);
    }

    public function testCreateFromRequestWithoutFilters()
    {
        $request = new Request();

        $attributes = [
            'resource_class' => DummyGroup::class,
            'collection_operation_name' => 'get',
        ];

        $resourceMetadata = new ResourceMetadata(
            null,
            null,
            null,
            null,
            ['get' => []]
        );

        $decoratedProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $decoratedProphecy->createFromRequest($request, false, $attributes)->willReturn([])->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyGroup::class)->willReturn($resourceMetadata)->shouldBeCalled();

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);

        $serializerContextBuilderFilter = new SerializerFilterContextBuilder($resourceMetadataFactoryProphecy->reveal(), $filterLocatorProphecy->reveal(), $decoratedProphecy->reveal());
        $serializerContextBuilderFilter->createFromRequest($request, false, $attributes);
    }

    public function testCreateFromRequestWithoutAttributes()
    {
        $request = new Request([], [], [
            '_api_resource_class' => DummyGroup::class,
            '_api_collection_operation_name' => 'get',
        ]);

        $attributes = [
            'resource_class' => DummyGroup::class,
            'collection_operation_name' => 'get',
            'identifiers' => [
                'id' => [DummyGroup::class, 'id'],
            ],
            'has_composite_identifier' => false,
            'receive' => true,
            'respond' => true,
            'persist' => true,
        ];

        $resourceMetadata = new ResourceMetadata(
            null,
            null,
            null,
            null,
            ['get' => ['filters' => ['dummy_group.group', 'dummy_group.search', 'dummy_group.nonexistent']]]
        );

        $decoratedProphecy = $this->prophesize(SerializerContextBuilderInterface::class);
        $decoratedProphecy->createFromRequest($request, true, $attributes)->willReturn([])->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyGroup::class)->willReturn($resourceMetadata)->shouldBeCalled();

        $dummyGroupGroupFilterProphecy = $this->prophesize(SerializerFilterInterface::class);
        $dummyGroupGroupFilterProphecy->apply($request, true, $attributes, [])->shouldBeCalled();

        $dummyGroupSearchFilterProphecy = $this->prophesize(FilterInterface::class);

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filterLocatorProphecy->has('dummy_group.group')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('dummy_group.group')->willReturn($dummyGroupGroupFilterProphecy->reveal())->shouldBeCalled();
        $filterLocatorProphecy->has('dummy_group.search')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('dummy_group.search')->willReturn($dummyGroupSearchFilterProphecy->reveal())->shouldBeCalled();
        $filterLocatorProphecy->has('dummy_group.nonexistent')->willReturn(false)->shouldBeCalled();

        $serializerContextBuilderFilter = new SerializerFilterContextBuilder($resourceMetadataFactoryProphecy->reveal(), $filterLocatorProphecy->reveal(), $decoratedProphecy->reveal());
        $serializerContextBuilderFilter->createFromRequest($request, true);
    }

    public function testCreateFromRequestThrowsExceptionWithoutAttributesAndRequestAttributes()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Request attributes are not valid.');

        $request = new Request();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $decoratedProphecy = $this->prophesize(SerializerContextBuilderInterface::class);

        $serializerContextBuilderFilter = new SerializerFilterContextBuilder($resourceMetadataFactoryProphecy->reveal(), $filterLocatorProphecy->reveal(), $decoratedProphecy->reveal());
        $serializerContextBuilderFilter->createFromRequest($request, true);
    }
}
