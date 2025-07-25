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

namespace ApiPlatform\Tests\Symfony\Bundle\DependencyInjection\Compiler;

use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\DeprecateMercurePublisherPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\BaseNode;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class DeprecateMercurePublisherPassTest extends TestCase
{
    use ProphecyTrait;

    public function testProcess(): void
    {
        $deprecateMercurePublisherPass = new DeprecateMercurePublisherPass();

        $this->assertInstanceOf(CompilerPassInterface::class, $deprecateMercurePublisherPass);

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $aliasProphecy = $this->prophesize(Alias::class);

        $containerBuilderProphecy
            ->hasDefinition('api_platform.doctrine.listener.mercure.publish')
            ->willReturn(true);

        $containerBuilderProphecy
            ->setAlias('api_platform.doctrine.listener.mercure.publish', 'api_platform.doctrine.orm.listener.mercure.publish')
            ->willReturn($aliasProphecy->reveal())
            ->shouldBeCalled();

        $setDeprecatedArgs = method_exists(BaseNode::class, 'getDeprecation')
            ? ['api-platform/core', '2.6', 'Using "%alias_id%" service is deprecated since API Platform 2.6. Use "api_platform.doctrine.orm.listener.mercure.publish" instead.']
            : ['Using "%alias_id%" service is deprecated since API Platform 2.6. Use "api_platform.doctrine.orm.listener.mercure.publish" instead.'];

        $aliasProphecy
            ->setDeprecated(...$setDeprecatedArgs)
            ->willReturn($aliasProphecy->reveal())
            ->shouldBeCalled();

        $deprecateMercurePublisherPass->process($containerBuilderProphecy->reveal());
    }

    public function testProcessWithoutDefinition(): void
    {
        $deprecateMercurePublisherPass = new DeprecateMercurePublisherPass();
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);

        $containerBuilderProphecy
            ->hasDefinition('api_platform.doctrine.listener.mercure.publish')
            ->willReturn(false);

        $containerBuilderProphecy
            ->setAlias('api_platform.doctrine.listener.mercure.publish', 'api_platform.doctrine.orm.listener.mercure.publish')
            ->shouldNotBeCalled();

        $deprecateMercurePublisherPass->process($containerBuilderProphecy->reveal());
    }
}
