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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\DataProvider;

use ApiPlatform\Core\Bridge\Symfony\Bundle\DataProvider\TraceableChainItemDataProvider;
use ApiPlatform\Core\DataProvider\ChainItemDataProvider;
use ApiPlatform\Core\DataProvider\DenormalizedIdentifiersAwareItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Exception\ResourceClassNotSupportedException;
use PHPUnit\Framework\TestCase;

/**
 * @author Anthony GRASSIOT <antograssiot@free.fr>
 */
class TraceableChainItemDataCollectorTest extends TestCase
{
    /** @dataProvider dataProviderProvider */
    public function testGetItem($provider, $context, $expected)
    {
        $dataProvider = new TraceableChainItemDataProvider($provider);
        $dataProvider->getItem('', '', null, $context);

        $result = $dataProvider->getProvidersResponse();
        $this->assertCount(\count($expected), $result);
        $this->assertEmpty(array_filter($result, function ($key) {
            if (\PHP_VERSION_ID >= 80000) {
                return !str_starts_with($key, ItemDataProviderInterface::class.'@anonymous');
            }

            return !str_starts_with($key, 'class@anonymous');
        }, \ARRAY_FILTER_USE_KEY));
        $this->assertSame($expected, array_values($result));
        $this->assertSame($context, $dataProvider->getContext());
    }

    /**
     * @dataProvider deprecatedDataProviderProvider
     *
     * @group legacy
     */
    public function testDeprecatedGetItem($provider, $context, $expected)
    {
        $dataProvider = new TraceableChainItemDataProvider($provider);
        $dataProvider->getItem('', '', null, $context);

        $result = $dataProvider->getProvidersResponse();
        $this->assertCount(\count($expected), $result);
        $this->assertEmpty(array_filter($result, function ($key) {
            if (\PHP_VERSION_ID >= 80000) {
                return !str_starts_with($key, ItemDataProviderInterface::class.'@anonymous');
            }

            return !str_starts_with($key, 'class@anonymous');
        }, \ARRAY_FILTER_USE_KEY));
        $this->assertSame($expected, array_values($result));
        $this->assertSame($context, $dataProvider->getContext());
    }

    public function dataProviderProvider(): iterable
    {
        yield 'Not a ChainItemDataProvider' => [
            new class() implements ItemDataProviderInterface {
                public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
                {
                    return null;
                }
            },
            ['some_context'],
            [],
        ];

        yield 'Empty ChainItemDataProvider' => [
            new ChainItemDataProvider([]),
            ['some_context'],
            [],
        ];

        yield 'ChainItemDataProvider' => [
            new ChainItemDataProvider([
                new class() implements ItemDataProviderInterface, RestrictedDataProviderInterface {
                    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
                    {
                        return false;
                    }

                    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
                    {
                        return null;
                    }
                },
                new class() implements ItemDataProviderInterface, RestrictedDataProviderInterface, DenormalizedIdentifiersAwareItemDataProviderInterface {
                    public function getItem(string $resourceClass, /* array */ $id, string $operationName = null, array $context = [])
                    {
                        return null;
                    }

                    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
                    {
                        return true;
                    }
                },
                new class() implements ItemDataProviderInterface {
                    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
                    {
                        return null;
                    }
                },
            ]),
            ['some_context'],
            [false, true, null],
        ];
    }

    public function deprecatedDataProviderProvider(): iterable
    {
        yield 'deprecated ChainItemDataProvider - ResourceClassNotSupportedException' => [
            new ChainItemDataProvider([
                new class() implements ItemDataProviderInterface {
                    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
                    {
                        throw new ResourceClassNotSupportedException('nope');
                    }
                },
                new class() implements ItemDataProviderInterface {
                    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
                    {
                        return null;
                    }
                },
            ]),
            ['some_context'],
            [false, true],
        ];
    }
}
