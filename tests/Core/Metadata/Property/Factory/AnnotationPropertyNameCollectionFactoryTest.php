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

namespace ApiPlatform\Core\Tests\Metadata\Property\Factory;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Metadata\Property\Factory\AnnotationPropertyNameCollectionFactory;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Exception\ResourceClassNotFoundException;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyPhp8;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\UpperCaseIdentifierDummy;
use Doctrine\Common\Annotations\Reader;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @group legacy
 */
class AnnotationPropertyNameCollectionFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider dependenciesProvider
     */
    public function testCreate($decorated, array $results)
    {
        $reader = $this->prophesize(Reader::class);
        $reader->getPropertyAnnotation(new \ReflectionProperty(Dummy::class, 'name'), ApiProperty::class)->willReturn(new ApiProperty())->shouldBeCalled();
        $reader->getPropertyAnnotation(Argument::type(\ReflectionProperty::class), ApiProperty::class)->willReturn(null)->shouldBeCalled();
        $reader->getMethodAnnotation(new \ReflectionMethod(Dummy::class, 'getName'), ApiProperty::class)->willReturn(new ApiProperty())->shouldBeCalled();
        $reader->getMethodAnnotation(new \ReflectionMethod(Dummy::class, 'getAlias'), ApiProperty::class)->willReturn(new ApiProperty())->shouldBeCalled();
        $reader->getMethodAnnotation(new \ReflectionMethod(Dummy::class, 'staticMethod'), ApiProperty::class)->shouldNotBeCalled();
        $reader->getMethodAnnotation(Argument::type(\ReflectionMethod::class), ApiProperty::class)->willReturn(null)->shouldBeCalled();

        $factory = new AnnotationPropertyNameCollectionFactory($reader->reveal(), $decorated ? $decorated->reveal() : null);
        $metadata = $factory->create(Dummy::class);

        $this->assertEquals($results, iterator_to_array($metadata));
    }

    public function dependenciesProvider(): array
    {
        $decoratedThrowsNotFound = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $decoratedThrowsNotFound->create(Dummy::class, [])->willThrow(new ResourceClassNotFoundException())->shouldBeCalled();

        $decoratedReturnParent = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $decoratedReturnParent->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['foo']))->shouldBeCalled();

        return [
            [null, ['name', 'alias']],
            [$decoratedThrowsNotFound, ['name', 'alias']],
            [$decoratedReturnParent, ['name', 'alias', 'foo']],
        ];
    }

    /**
     * @requires PHP 8.0
     */
    public function testCreateAttribute()
    {
        $factory = new AnnotationPropertyNameCollectionFactory();
        $metadata = $factory->create(DummyPhp8::class);

        $this->assertSame(['id', 'foo'], iterator_to_array($metadata));
    }

    /**
     * @dataProvider upperCaseDependenciesProvider
     */
    public function testUpperCaseCreate($decorated, array $results)
    {
        $reader = $this->prophesize(Reader::class);
        $reader->getPropertyAnnotation(new \ReflectionProperty(UpperCaseIdentifierDummy::class, 'name'), ApiProperty::class)->willReturn(new ApiProperty())->shouldBeCalled();
        $reader->getPropertyAnnotation(new \ReflectionProperty(UpperCaseIdentifierDummy::class, 'Uuid'), ApiProperty::class)->willReturn(new ApiProperty())->shouldBeCalled();
        $reader->getPropertyAnnotation(Argument::type(\ReflectionProperty::class), ApiProperty::class)->willReturn(null)->shouldBeCalled();
        $reader->getMethodAnnotation(new \ReflectionMethod(UpperCaseIdentifierDummy::class, 'getName'), ApiProperty::class)->willReturn(new ApiProperty())->shouldBeCalled();
        $reader->getMethodAnnotation(new \ReflectionMethod(UpperCaseIdentifierDummy::class, 'getUuid'), ApiProperty::class)->willReturn(new ApiProperty())->shouldBeCalled();
        $reader->getMethodAnnotation(Argument::type(\ReflectionMethod::class), ApiProperty::class)->willReturn(null)->shouldBeCalled();

        $factory = new AnnotationPropertyNameCollectionFactory($reader->reveal(), $decorated ? $decorated->reveal() : null);
        $metadata = $factory->create(UpperCaseIdentifierDummy::class);

        $this->assertEquals($results, iterator_to_array($metadata));
    }

    public function upperCaseDependenciesProvider(): array
    {
        $decoratedThrowsNotFound = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $decoratedThrowsNotFound->create(UpperCaseIdentifierDummy::class, [])->willThrow(new ResourceClassNotFoundException())->shouldBeCalled();

        return [
            [null, ['Uuid', 'name']],
            [$decoratedThrowsNotFound, ['Uuid', 'name']],
        ];
    }

    public function testClassDoesNotExist()
    {
        $this->expectException(ResourceClassNotFoundException::class);
        $this->expectExceptionMessage('The resource class "\\DoNotExist" does not exist.');

        $reader = $this->prophesize(Reader::class);

        $factory = new AnnotationPropertyNameCollectionFactory($reader->reveal());
        $factory->create('\DoNotExist');
    }
}
