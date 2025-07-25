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

namespace ApiPlatform\Tests\GraphQl\Resolver\Factory;

use ApiPlatform\GraphQl\Resolver\Factory\CollectionResolverFactory;
use ApiPlatform\GraphQl\Resolver\Stage\ReadStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\SecurityPostDenormalizeStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\SecurityStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\SerializeStageInterface;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Tests\ProphecyTrait;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class CollectionResolverFactoryTest extends TestCase
{
    use ProphecyTrait;

    private $collectionResolverFactory;
    private $readStageProphecy;
    private $securityStageProphecy;
    private $securityPostDenormalizeStageProphecy;
    private $serializeStageProphecy;
    private $queryResolverLocatorProphecy;
    private $requestStackProphecy;

    protected function setUp(): void
    {
        $this->readStageProphecy = $this->prophesize(ReadStageInterface::class);
        $this->securityStageProphecy = $this->prophesize(SecurityStageInterface::class);
        $this->securityPostDenormalizeStageProphecy = $this->prophesize(SecurityPostDenormalizeStageInterface::class);
        $this->serializeStageProphecy = $this->prophesize(SerializeStageInterface::class);
        $this->queryResolverLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $this->requestStackProphecy = $this->prophesize(RequestStack::class);

        $this->collectionResolverFactory = new CollectionResolverFactory(
            $this->readStageProphecy->reveal(),
            $this->securityStageProphecy->reveal(),
            $this->securityPostDenormalizeStageProphecy->reveal(),
            $this->serializeStageProphecy->reveal(),
            $this->queryResolverLocatorProphecy->reveal(),
            $this->requestStackProphecy->reveal()
        );
    }

    public function testResolve(): void
    {
        $resourceClass = 'stdClass';
        $rootClass = 'rootClass';
        $operationName = 'collection_query';
        $operation = (new QueryCollection())->withName($operationName);
        $source = ['testField' => 0];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $info->fieldName = 'testField';
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => true, 'is_mutation' => false, 'is_subscription' => false];

        $request = new Request();
        $attributesParameterBagProphecy = $this->prophesize(ParameterBag::class);
        $attributesParameterBagProphecy->get('_graphql_collections_args', [])->willReturn(['collection_args']);
        $attributesParameterBagProphecy->set('_graphql_collections_args', [$resourceClass => $args, 'collection_args'])->shouldBeCalled();
        $request->attributes = $attributesParameterBagProphecy->reveal();
        $this->requestStackProphecy->getCurrentRequest()->willReturn($request);

        $readStageCollection = [new \stdClass()];
        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($readStageCollection);

        $this->securityStageProphecy->__invoke($resourceClass, $operation, $resolverContext + [
            'extra_variables' => [
                'object' => $readStageCollection,
            ],
        ])->shouldNotBeCalled();
        $this->securityPostDenormalizeStageProphecy->__invoke($resourceClass, $operation, $resolverContext + [
            'extra_variables' => [
                'object' => $readStageCollection,
                'previous_object' => $readStageCollection,
            ],
        ])->shouldNotBeCalled();

        $serializeStageData = ['serialized'];
        $this->serializeStageProphecy->__invoke($readStageCollection, $resourceClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($serializeStageData);

        $this->assertSame($serializeStageData, ($this->collectionResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info));
    }

    public function testResolveFieldNotInSource(): void
    {
        $resourceClass = 'stdClass';
        $rootClass = 'rootClass';
        $operationName = 'collection_query';
        $operation = (new QueryCollection())->withName($operationName);
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $info->fieldName = 'testField';
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => true, 'is_mutation' => false, 'is_subscription' => false];

        $readStageCollection = [new \stdClass()];
        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operation, $resolverContext)->shouldNotBeCalled();

        $this->securityStageProphecy->__invoke($resourceClass, $operation, $resolverContext + [
            'extra_variables' => [
                'object' => $readStageCollection,
            ],
        ])->shouldNotBeCalled();
        $this->securityPostDenormalizeStageProphecy->__invoke($resourceClass, $operation, $resolverContext + [
            'extra_variables' => [
                'object' => $readStageCollection,
                'previous_object' => $readStageCollection,
            ],
        ])->shouldNotBeCalled();

        // Null should be returned if the field isn't in the source - as its lack of presence will be due to @ApiProperty security stripping unauthorized fields
        $this->assertNull(($this->collectionResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info));
    }

    public function testResolveNullSource(): void
    {
        $resourceClass = 'stdClass';
        $rootClass = 'rootClass';
        $operationName = 'collection_query';
        $operation = (new QueryCollection())->withName($operationName);
        $source = null;
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => true, 'is_mutation' => false, 'is_subscription' => false];

        $request = new Request();
        $attributesParameterBagProphecy = $this->prophesize(ParameterBag::class);
        $attributesParameterBagProphecy->get('_graphql_collections_args', [])->willReturn(['collection_args']);
        $attributesParameterBagProphecy->set('_graphql_collections_args', [$resourceClass => $args, 'collection_args'])->shouldBeCalled();
        $request->attributes = $attributesParameterBagProphecy->reveal();
        $this->requestStackProphecy->getCurrentRequest()->willReturn($request);

        $readStageCollection = [new \stdClass()];
        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($readStageCollection);

        $this->securityStageProphecy->__invoke($resourceClass, $operation, $resolverContext + [
            'extra_variables' => [
                'object' => $readStageCollection,
            ],
        ])->shouldBeCalled();
        $this->securityPostDenormalizeStageProphecy->__invoke($resourceClass, $operation, $resolverContext + [
            'extra_variables' => [
                'object' => $readStageCollection,
                'previous_object' => $readStageCollection,
            ],
        ])->shouldBeCalled();

        $serializeStageData = ['serialized'];
        $this->serializeStageProphecy->__invoke($readStageCollection, $resourceClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($serializeStageData);

        $this->assertSame($serializeStageData, ($this->collectionResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info));
    }

    public function testResolveNullResourceClass(): void
    {
        $resourceClass = null;
        $rootClass = 'rootClass';
        $operationName = 'collection_query';
        $operation = (new QueryCollection())->withName($operationName);
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();

        $this->assertNull(($this->collectionResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info));
    }

    public function testResolveNullRootClass(): void
    {
        $resourceClass = 'stdClass';
        $rootClass = null;
        $operationName = 'collection_query';
        $operation = (new QueryCollection())->withName($operationName);
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();

        $this->assertNull(($this->collectionResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info));
    }

    public function testResolveBadReadStageCollection(): void
    {
        $resourceClass = 'stdClass';
        $rootClass = 'rootClass';
        $operationName = 'collection_query';
        $operation = (new QueryCollection())->withName($operationName);
        $source = null;
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => true, 'is_mutation' => false, 'is_subscription' => false];

        $readStageCollection = new \stdClass();
        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($readStageCollection);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Collection from read stage should be iterable.');

        ($this->collectionResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info);
    }

    public function testResolveCustom(): void
    {
        $resourceClass = 'stdClass';
        $rootClass = 'rootClass';
        $operationName = 'collection_query';
        $operation = (new QueryCollection())->withResolver('query_resolver_id')->withName($operationName);
        $source = null;
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => true, 'is_mutation' => false, 'is_subscription' => false];

        $readStageCollection = [new \stdClass()];
        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($readStageCollection);

        $customCollection = [new \stdClass()];
        $customCollection[0]->field = 'foo';
        $this->queryResolverLocatorProphecy->get('query_resolver_id')->shouldBeCalled()->willReturn(function () use ($customCollection) {
            return $customCollection;
        });

        $this->securityStageProphecy->__invoke($resourceClass, $operation, $resolverContext + [
            'extra_variables' => [
                'object' => $customCollection,
            ],
        ])->shouldBeCalled();
        $this->securityPostDenormalizeStageProphecy->__invoke($resourceClass, $operation, $resolverContext + [
            'extra_variables' => [
                'object' => $customCollection,
                'previous_object' => $customCollection,
            ],
        ])->shouldBeCalled();

        $serializeStageData = ['serialized'];
        $this->serializeStageProphecy->__invoke($customCollection, $resourceClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($serializeStageData);

        $this->assertSame($serializeStageData, ($this->collectionResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info));
    }
}
