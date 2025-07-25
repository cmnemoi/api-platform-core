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

namespace ApiPlatform\Tests\JsonLd\Action;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\JsonLd\Action\ContextAction;
use ApiPlatform\JsonLd\ContextBuilderInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 *
 * @group legacy
 */
class ContextActionTest extends TestCase
{
    use ProphecyTrait;

    public function testContextActionWithEntrypoint()
    {
        $contextBuilderProphecy = $this->prophesize(ContextBuilderInterface::class);
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $contextBuilderProphecy->getEntrypointContext()->willReturn(['/entrypoints']);
        $contextAction = new ContextAction($contextBuilderProphecy->reveal(), $resourceNameCollectionFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal());

        $this->assertEquals(['@context' => ['/entrypoints']], $contextAction('Entrypoint'));
    }

    public function testContextActionWithContexts()
    {
        $contextBuilderProphecy = $this->prophesize(ContextBuilderInterface::class);
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $contextBuilderProphecy->getBaseContext()->willReturn(['/contexts']);
        $contextAction = new ContextAction($contextBuilderProphecy->reveal(), $resourceNameCollectionFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal());

        $this->assertEquals(['@context' => ['/contexts']], $contextAction('Error'));
    }

    public function testContextActionWithResourceClass()
    {
        $contextBuilderProphecy = $this->prophesize(ContextBuilderInterface::class);
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['dummy']));
        $contextAction = new ContextAction($contextBuilderProphecy->reveal(), $resourceNameCollectionFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal());
        $contextBuilderProphecy->getResourceContext('dummy')->willReturn(['/dummies']);

        $resourceMetadataFactoryProphecy->create('dummy')->shouldBeCalled()->willReturn(
            new ResourceMetadata('dummy', 'dummy', '#dummy', ['get' => ['method' => 'GET'], 'put' => ['method' => 'PUT']], ['get' => ['method' => 'GET'], 'post' => ['method' => 'POST'], 'custom' => ['method' => 'GET', 'path' => '/foo'], 'custom2' => ['method' => 'POST', 'path' => '/foo']], [])
        );
        $this->assertEquals(['@context' => ['/dummies']], $contextAction('dummy'));
    }

    public function testContextActionWithThrown()
    {
        $this->expectException(NotFoundHttpException::class);

        $contextBuilderProphecy = $this->prophesize(ContextBuilderInterface::class);
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['gerard']));
        $contextAction = new ContextAction($contextBuilderProphecy->reveal(), $resourceNameCollectionFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal());

        $resourceMetadataFactoryProphecy->create('gerard')->shouldBeCalled()->willReturn(
            new ResourceMetadata('gerard', 'gerard', '#dummy', ['get' => ['method' => 'GET'], 'put' => ['method' => 'PUT']], ['get' => ['method' => 'GET'], 'post' => ['method' => 'POST'], 'custom' => ['method' => 'GET', 'path' => '/foo'], 'custom2' => ['method' => 'POST', 'path' => '/foo']], [])
        );
        $contextAction('dummy');
    }
}
