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

namespace ApiPlatform\Core\Tests\GraphQl\Resolver\Stage;

use ApiPlatform\GraphQl\Resolver\Stage\WriteStage;
use ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class WriteStageTest extends TestCase
{
    use ProphecyTrait;

    /** @var WriteStage */
    private $writeStage;
    private $processorProphecy;
    private $serializerContextBuilderProphecy;

    protected function setUp(): void
    {
        $this->processorProphecy = $this->prophesize(ProcessorInterface::class);
        $this->serializerContextBuilderProphecy = $this->prophesize(SerializerContextBuilderInterface::class);

        $this->writeStage = new WriteStage(
            $this->processorProphecy->reveal(),
            $this->serializerContextBuilderProphecy->reveal()
        );
    }

    public function testNoData(): void
    {
        $resourceClass = 'myResource';
        $operationName = 'item_query';
        /** @var Operation $operation */
        $operation = (new Query())->withName('item_query');

        $result = ($this->writeStage)(null, $resourceClass, $operation, []);

        $this->assertNull($result);
    }

    public function testApplyDisabled(): void
    {
        $operationName = 'item_query';
        $resourceClass = 'myResource';
        /** @var Operation $operation */
        $operation = (new Query())->withName('item_query')->withWrite(false);

        $data = new \stdClass();
        $result = ($this->writeStage)($data, $resourceClass, $operation, []);

        $this->assertSame($data, $result);
    }

    public function testApply(): void
    {
        $operationName = 'create';
        $resourceClass = 'myResource';
        $context = [];
        /** @var Operation $operation */
        $operation = (new Mutation())->withName($operationName);

        $denormalizationContext = ['denormalization' => true];
        $this->serializerContextBuilderProphecy->create($resourceClass, $operation, $context, false)->willReturn($denormalizationContext);

        $data = new \stdClass();
        $processedData = new \stdClass();
        $this->processorProphecy->process($data, $operation, [], ['operation' => $operation] + $denormalizationContext)->shouldBeCalled()->willReturn($processedData);

        $result = ($this->writeStage)($data, $resourceClass, $operation, $context);

        $this->assertSame($processedData, $result);
    }
}
