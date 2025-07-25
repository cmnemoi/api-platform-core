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

namespace ApiPlatform\Core\Tests\GraphQl\Resolver\Factory;

use ApiPlatform\GraphQl\Resolver\Factory\ItemResolverFactory;
use ApiPlatform\GraphQl\Resolver\Stage\ReadStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\SecurityPostDenormalizeStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\SecurityStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\SerializeStageInterface;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\ProphecyTrait;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ItemResolverFactoryTest extends TestCase
{
    use ProphecyTrait;

    private $itemResolverFactory;
    private $readStageProphecy;
    private $securityStageProphecy;
    private $securityPostDenormalizeStageProphecy;
    private $serializeStageProphecy;
    private $queryResolverLocatorProphecy;

    protected function setUp(): void
    {
        $this->readStageProphecy = $this->prophesize(ReadStageInterface::class);
        $this->securityStageProphecy = $this->prophesize(SecurityStageInterface::class);
        $this->securityPostDenormalizeStageProphecy = $this->prophesize(SecurityPostDenormalizeStageInterface::class);
        $this->serializeStageProphecy = $this->prophesize(SerializeStageInterface::class);
        $this->queryResolverLocatorProphecy = $this->prophesize(ContainerInterface::class);

        $this->itemResolverFactory = new ItemResolverFactory(
            $this->readStageProphecy->reveal(),
            $this->securityStageProphecy->reveal(),
            $this->securityPostDenormalizeStageProphecy->reveal(),
            $this->serializeStageProphecy->reveal(),
            $this->queryResolverLocatorProphecy->reveal()
        );
    }

    /**
     * @dataProvider itemResourceProvider
     *
     * @param object|null $readStageItem
     */
    public function testResolve(?string $resourceClass, string $determinedResourceClass, $readStageItem): void
    {
        $rootClass = 'rootClass';
        $operationName = 'item_query';
        $operation = (new Query())->withName($operationName);
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => false];

        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($readStageItem);

        $this->securityStageProphecy->__invoke($determinedResourceClass, $operation, $resolverContext + [
            'extra_variables' => [
                'object' => $readStageItem,
            ],
        ])->shouldBeCalled();
        $this->securityPostDenormalizeStageProphecy->__invoke($determinedResourceClass, $operation, $resolverContext + [
            'extra_variables' => [
                'object' => $readStageItem,
                'previous_object' => $readStageItem,
            ],
        ])->shouldBeCalled();

        $serializeStageData = ['serialized'];
        $this->serializeStageProphecy->__invoke($readStageItem, $determinedResourceClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($serializeStageData);

        $this->assertSame($serializeStageData, ($this->itemResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info));
    }

    public function itemResourceProvider(): array
    {
        return [
            'nominal' => ['stdClass', 'stdClass', new \stdClass()],
            'null item' => ['stdClass', 'stdClass', null],
            'null resource class' => [null, 'stdClass', new \stdClass()],
        ];
    }

    public function testResolveNested(): void
    {
        $source = ['nested' => ['already_serialized']];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $info->fieldName = 'nested';

        $this->assertSame(['already_serialized'], ($this->itemResolverFactory)('resourceClass')($source, [], null, $info));
    }

    public function testResolveNestedNullValue(): void
    {
        $source = ['nestedNullValue' => null];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $info->fieldName = 'nestedNullValue';

        $this->assertNull(($this->itemResolverFactory)('resourceClass')($source, [], null, $info));
    }

    public function testResolveBadReadStageItem(): void
    {
        $resourceClass = 'stdClass';
        $rootClass = 'rootClass';
        $operationName = 'item_query';
        $operation = (new Query())->withName($operationName);
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => false];

        $readStageItem = [];
        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($readStageItem);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Item from read stage should be a nullable object.');

        ($this->itemResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info);
    }

    public function testResolveNoResourceNoItem(): void
    {
        $resourceClass = null;
        $rootClass = 'rootClass';
        $operationName = 'item_query';
        $operation = (new Query())->withName($operationName);
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => false];

        $readStageItem = null;
        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($readStageItem);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Resource class cannot be determined.');

        ($this->itemResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info);
    }

    public function testResolveBadItem(): void
    {
        $resourceClass = Dummy::class;
        $rootClass = 'rootClass';
        $operationName = 'item_query';
        $operation = (new Query())->withName($operationName);
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => false];

        $readStageItem = new \stdClass();
        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($readStageItem);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Resolver only handles items of class Dummy but retrieved item is of class stdClass.');

        ($this->itemResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info);
    }

    public function testResolveCustom(): void
    {
        $resourceClass = 'stdClass';
        $rootClass = 'rootClass';
        $operationName = 'custom_query';
        $operation = (new Query())->withResolver('query_resolver_id')->withName($operationName);
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => false];

        $readStageItem = new \stdClass();
        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($readStageItem);

        $customItem = new \stdClass();
        $customItem->field = 'foo';
        $this->queryResolverLocatorProphecy->get('query_resolver_id')->shouldBeCalled()->willReturn(function () use ($customItem) {
            return $customItem;
        });

        $this->securityStageProphecy->__invoke($resourceClass, $operation, $resolverContext + [
            'extra_variables' => [
                'object' => $customItem,
            ],
        ])->shouldBeCalled();
        $this->securityPostDenormalizeStageProphecy->__invoke($resourceClass, $operation, $resolverContext + [
            'extra_variables' => [
                'object' => $customItem,
                'previous_object' => $customItem,
            ],
        ])->shouldBeCalled();

        $serializeStageData = ['serialized'];
        $this->serializeStageProphecy->__invoke($customItem, $resourceClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($serializeStageData);

        $this->assertSame($serializeStageData, ($this->itemResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info));
    }

    public function testResolveCustomBadItem(): void
    {
        $resourceClass = 'stdClass';
        $rootClass = 'rootClass';
        $operationName = 'custom_query';
        $operation = (new Query())->withResolver('query_resolver_id')->withName($operationName);
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => false];

        $readStageItem = new \stdClass();
        $this->readStageProphecy->__invoke($resourceClass, $rootClass, $operation, $resolverContext)->shouldBeCalled()->willReturn($readStageItem);

        $customItem = new Dummy();
        $this->queryResolverLocatorProphecy->get('query_resolver_id')->shouldBeCalled()->willReturn(function () use ($customItem) {
            return $customItem;
        });

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Custom query resolver "query_resolver_id" has to return an item of class stdClass but returned an item of class Dummy.');

        ($this->itemResolverFactory)($resourceClass, $rootClass, $operation)($source, $args, null, $info);
    }
}
