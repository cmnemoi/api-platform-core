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

namespace ApiPlatform\Tests\Hal\Serializer;

use ApiPlatform\Api\Entrypoint;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Hal\Serializer\EntrypointNormalizer;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @group legacy
 */
class EntrypointNormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testSupportNormalization()
    {
        $collection = new ResourceNameCollection();
        $entrypoint = new Entrypoint($collection);

        $factoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);

        $normalizer = new EntrypointNormalizer($factoryProphecy->reveal(), $iriConverterProphecy->reveal(), $urlGeneratorProphecy->reveal());

        $this->assertTrue($normalizer->supportsNormalization($entrypoint, EntrypointNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization($entrypoint, 'json'));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass(), EntrypointNormalizer::FORMAT));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    public function testNormalize()
    {
        $collection = new ResourceNameCollection([Dummy::class]);
        $entrypoint = new Entrypoint($collection);
        $factoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $factoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('Dummy', null, null, null, ['get']))->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResourceClass(Dummy::class)->willReturn('/api/dummies')->shouldBeCalled();

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_entrypoint')->willReturn('/api')->shouldBeCalled();

        $normalizer = new EntrypointNormalizer($factoryProphecy->reveal(), $iriConverterProphecy->reveal(), $urlGeneratorProphecy->reveal());

        $expected = [
            '_links' => [
                'self' => [
                    'href' => '/api',
                ],
                'dummy' => [
                    'href' => '/api/dummies',
                ],
            ],
        ];
        $this->assertEquals($expected, $normalizer->normalize($entrypoint, EntrypointNormalizer::FORMAT));
    }
}
