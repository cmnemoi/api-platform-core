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

namespace ApiPlatform\Tests\Hydra\Serializer;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Hydra\Serializer\PartialCollectionViewNormalizer;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SoMany;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @group legacy
 */
class PartialCollectionViewNormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testNormalizeDoesNotChangeSubLevel()
    {
        $decoratedNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedNormalizerProphecy->normalize(Argument::any(), null, ['jsonld_sub_level' => true])->willReturn(['foo' => 'bar'])->shouldBeCalled();
        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $normalizer = new PartialCollectionViewNormalizer($decoratedNormalizerProphecy->reveal(), 'page', 'pagination', $resourceMetadataFactory->reveal());
        $this->assertEquals(['foo' => 'bar'], $normalizer->normalize(new \stdClass(), null, ['jsonld_sub_level' => true]));
    }

    public function testNormalizeDoesNotChangeWhenNoFilterNorPagination()
    {
        $decoratedNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedNormalizerProphecy->normalize(Argument::any(), null, Argument::type('array'))->willReturn(['foo' => 'bar'])->shouldBeCalled();
        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $normalizer = new PartialCollectionViewNormalizer($decoratedNormalizerProphecy->reveal(), 'page', 'pagination', $resourceMetadataFactory->reveal());
        $this->assertEquals(['foo' => 'bar'], $normalizer->normalize(new \stdClass(), null, ['request_uri' => '/?page=1&pagination=1']));
    }

    public function testNormalizePaginator()
    {
        $this->assertEquals(
            [
                'hydra:totalItems' => 40,
                'foo' => 'bar',
                'hydra:view' => [
                    '@id' => '/?_page=3',
                    '@type' => 'hydra:PartialCollectionView',
                    'hydra:first' => '/?_page=1',
                    'hydra:last' => '/?_page=20',
                    'hydra:previous' => '/?_page=2',
                    'hydra:next' => '/?_page=4',
                ],
            ],
            $this->normalizePaginator()
        );
    }

    public function testNormalizePartialPaginator()
    {
        $this->assertEquals(
            [
                'foo' => 'bar',
                'hydra:view' => [
                    '@id' => '/?_page=3',
                    '@type' => 'hydra:PartialCollectionView',
                    'hydra:previous' => '/?_page=2',
                    'hydra:next' => '/?_page=4',
                ],
            ],
            $this->normalizePaginator(true)
        );
    }

    public function testNormalizeWithCursorBasedPagination(): void
    {
        self::assertEquals(
            [
                'foo' => 'bar',
                'hydra:totalItems' => 40,
                'hydra:view' => [
                    '@id' => '/',
                    '@type' => 'hydra:PartialCollectionView',
                    'hydra:previous' => '/?id%5Bgt%5D=1',
                    'hydra:next' => '/?id%5Blt%5D=2',
                ],
            ],
            $this->normalizePaginator(false, true)
        );
    }

    private function normalizePaginator(bool $partial = false, bool $cursor = false)
    {
        $paginatorProphecy = $this->prophesize($partial ? PartialPaginatorInterface::class : PaginatorInterface::class);
        $paginatorProphecy->getCurrentPage()->willReturn(3)->shouldBeCalled();

        $decoratedNormalize = ['foo' => 'bar'];

        if ($partial) {
            $paginatorProphecy->getItemsPerPage()->willReturn(42)->shouldBeCalled();
            $paginatorProphecy->count()->willReturn(42)->shouldBeCalled();
        } else {
            $decoratedNormalize['hydra:totalItems'] = 40;
            $paginatorProphecy->getLastPage()->willReturn(20)->shouldBeCalled();
        }

        $decoratedNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedNormalizerProphecy->normalize(Argument::type($partial ? PartialPaginatorInterface::class : PaginatorInterface::class), null, Argument::type('array'))->willReturn($decoratedNormalize)->shouldBeCalled();

        $resourceMetadataFactoryProphecy = null;

        if ($cursor) {
            $firstSoMany = new SoMany();
            $firstSoMany->id = 1;
            $firstSoMany->content = 'SoMany #1';

            $lastSoMany = new SoMany();
            $lastSoMany->id = 2;
            $lastSoMany->content = 'SoMany #2';

            $paginatorProphecy->rewind()->shouldBeCalledOnce();
            $paginatorProphecy->valid()->willReturn(true, true, false)->shouldBeCalledTimes(3);
            $paginatorProphecy->key()->willReturn(1, 2)->shouldBeCalledTimes(2);
            $paginatorProphecy->current()->willReturn($firstSoMany, $lastSoMany)->shouldBeCalledTimes(2);
            $paginatorProphecy->next()->shouldBeCalledTimes(2);

            $soManyMetadata = new ResourceMetadata(null, null, null, null, ['get' => ['pagination_via_cursor' => [['field' => 'id', 'direction' => 'desc']]]]);

            $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
            $resourceMetadataFactoryProphecy->create(SoMany::class)->willReturn($soManyMetadata)->shouldBeCalledOnce();
        }

        $normalizer = new PartialCollectionViewNormalizer($decoratedNormalizerProphecy->reveal(), '_page', 'pagination', $resourceMetadataFactoryProphecy ? $resourceMetadataFactoryProphecy->reveal() : null);

        return $normalizer->normalize($paginatorProphecy->reveal(), null, ['resource_class' => SoMany::class, 'collection_operation_name' => 'get']);
    }

    public function testSupportsNormalization()
    {
        $decoratedNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedNormalizerProphecy->willImplement(CacheableSupportsMethodInterface::class);
        $decoratedNormalizerProphecy->supportsNormalization(Argument::any(), null, Argument::type('array'))->willReturn(true)->shouldBeCalled();
        $decoratedNormalizerProphecy->hasCacheableSupportsMethod()->willReturn(true)->shouldBeCalled();
        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $normalizer = new PartialCollectionViewNormalizer($decoratedNormalizerProphecy->reveal(), 'page', 'pagination', $resourceMetadataFactory->reveal());
        $this->assertTrue($normalizer->supportsNormalization(new \stdClass()));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    public function testSetNormalizer()
    {
        $injectedNormalizer = $this->prophesize(NormalizerInterface::class)->reveal();

        $decoratedNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $decoratedNormalizerProphecy->willImplement(NormalizerAwareInterface::class);
        $decoratedNormalizerProphecy->setNormalizer(Argument::type(NormalizerInterface::class))->shouldBeCalled();
        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $normalizer = new PartialCollectionViewNormalizer($decoratedNormalizerProphecy->reveal(), 'page', 'pagination', $resourceMetadataFactory->reveal());
        $normalizer->setNormalizer($injectedNormalizer);
    }
}
