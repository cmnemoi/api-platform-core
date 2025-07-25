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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Validator\Metadata\Property;

use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaFormat;
use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaLengthRestriction;
use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaOneOfRestriction;
use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRegexRestriction;
use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\ValidatorPropertyMetadataFactory;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Tests\Fixtures\DummyAtLeastOneOfValidatedEntity;
use ApiPlatform\Tests\Fixtures\DummyIriWithValidationEntity;
use ApiPlatform\Tests\Fixtures\DummySequentiallyValidatedEntity;
use ApiPlatform\Tests\Fixtures\DummyValidatedEntity;
use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Intl\Countries;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Validator\Constraints\AtLeastOneOf;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;
use Symfony\Component\Validator\Mapping\Loader\AnnotationLoader;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class ValidatorPropertyMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    private $validatorClassMetadata;

    protected function setUp(): void
    {
        $this->validatorClassMetadata = new ClassMetadata(DummyValidatedEntity::class);
        (new AnnotationLoader(new AnnotationReader()))->loadClassMetadata($this->validatorClassMetadata);
    }

    public function testCreateWithPropertyWithRequiredConstraints()
    {
        $propertyMetadata = new PropertyMetadata(null, 'A dummy', true, true, null, null, null, false);
        $expectedPropertyMetadata = $propertyMetadata->withRequired(true);

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummy', [])->willReturn($propertyMetadata)->shouldBeCalled();

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)->willReturn($this->validatorClassMetadata)->shouldBeCalled();

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            []
        );
        $resultedPropertyMetadata = $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummy');

        $this->assertEquals($expectedPropertyMetadata, $resultedPropertyMetadata);
    }

    public function testCreateWithPropertyWithNotRequiredConstraints()
    {
        $propertyMetadata = new PropertyMetadata(null, 'A dummy date', true, true, null, null, null, false);
        $expectedPropertyMetadata = $propertyMetadata->withRequired(false);
        $expectedPropertyMetadata = $expectedPropertyMetadata->withIri('http://schema.org/Date');

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyDate', [])->willReturn($propertyMetadata)->shouldBeCalled();

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)->willReturn($this->validatorClassMetadata)->shouldBeCalled();

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            []
        );
        $resultedPropertyMetadata = $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyDate');

        $this->assertEquals($expectedPropertyMetadata, $resultedPropertyMetadata);
    }

    public function testCreateWithPropertyWithoutConstraints()
    {
        $propertyMetadata = new PropertyMetadata(null, 'A dummy id', true, true, null, null, null, true);
        $expectedPropertyMetadata = $propertyMetadata->withRequired(false);

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyId', [])->willReturn($propertyMetadata)->shouldBeCalled();

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)->willReturn($this->validatorClassMetadata)->shouldBeCalled();

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            []
        );
        $resultedPropertyMetadata = $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyId');

        $this->assertEquals($expectedPropertyMetadata, $resultedPropertyMetadata);
    }

    public function testCreateWithPropertyWithRightValidationGroupsAndRequiredConstraints()
    {
        $propertyMetadata = new PropertyMetadata(null, 'A dummy group', true, true, null, null, null, false);
        $expectedPropertyMetadata = $propertyMetadata->withRequired(true);

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyGroup', ['validation_groups' => ['dummy']])->willReturn($propertyMetadata)->shouldBeCalled();

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)->willReturn($this->validatorClassMetadata)->shouldBeCalled();

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            []
        );
        $resultedPropertyMetadata = $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyGroup', ['validation_groups' => ['dummy']]);

        $this->assertEquals($expectedPropertyMetadata, $resultedPropertyMetadata);
    }

    public function testCreateWithPropertyWithBadValidationGroupsAndRequiredConstraints()
    {
        $propertyMetadata = new PropertyMetadata(null, 'A dummy group', true, true, null, null, null, false);
        $expectedPropertyMetadata = $propertyMetadata->withRequired(false);

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyGroup', ['validation_groups' => ['ymmud']])->willReturn($propertyMetadata)->shouldBeCalled();

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)->willReturn($this->validatorClassMetadata)->shouldBeCalled();

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            []
        );
        $resultedPropertyMetadata = $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyGroup', ['validation_groups' => ['ymmud']]);

        $this->assertEquals($expectedPropertyMetadata, $resultedPropertyMetadata);
    }

    public function testCreateWithPropertyWithNonStringValidationGroupsAndRequiredConstraints()
    {
        $propertyMetadata = new PropertyMetadata(null, 'A dummy group', true, true, null, null, null, false);
        $expectedPropertyMetadata = $propertyMetadata->withRequired(false);

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyGroup', ['validation_groups' => [1312]])->willReturn($propertyMetadata)->shouldBeCalled();

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)->willReturn($this->validatorClassMetadata)->shouldBeCalled();

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            []
        );
        $resultedPropertyMetadata = $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyGroup', ['validation_groups' => [1312]]);

        $this->assertEquals($expectedPropertyMetadata, $resultedPropertyMetadata);
    }

    public function testCreateWithRequiredByDecorated()
    {
        $propertyMetadata = new PropertyMetadata(null, 'A dummy date', true, true, null, null, true, false, 'foo:bar');
        $expectedPropertyMetadata = clone $propertyMetadata;

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyDate', [])->willReturn($propertyMetadata)->shouldBeCalled();

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)->willReturn($this->validatorClassMetadata)->shouldBeCalled();

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            []
        );
        $resultedPropertyMetadata = $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummyDate');

        $this->assertEquals($expectedPropertyMetadata, $resultedPropertyMetadata);
    }

    public function testCreateWithPropertyWithValidationConstraints()
    {
        if (!class_exists(Countries::class)) {
            $this->markTestSkipped('symfony/intl not installed');
        }

        $validatorClassMetadata = new ClassMetadata(DummyIriWithValidationEntity::class);
        (new AnnotationLoader(new AnnotationReader()))->loadClassMetadata($validatorClassMetadata);

        $types = [
            'dummyUrl' => 'http://schema.org/url',
            'dummyEmail' => 'http://schema.org/email',
            'dummyUuid' => 'http://schema.org/identifier',
            'dummyCardScheme' => 'http://schema.org/identifier',
            'dummyBic' => 'http://schema.org/identifier',
            'dummyIban' => 'http://schema.org/identifier',
            'dummyDate' => 'http://schema.org/Date',
            'dummyDateTime' => 'http://schema.org/DateTime',
            'dummyTime' => 'http://schema.org/Time',
            'dummyImage' => 'http://schema.org/image',
            'dummyFile' => 'http://schema.org/MediaObject',
            'dummyCurrency' => 'http://schema.org/priceCurrency',
            'dummyIsbn' => 'http://schema.org/isbn',
            'dummyIssn' => 'http://schema.org/issn',
        ];

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        foreach ($types as $property => $iri) {
            $decoratedPropertyMetadataFactory->create(DummyIriWithValidationEntity::class, $property, [])->willReturn(new PropertyMetadata())->shouldBeCalled();
        }

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyIriWithValidationEntity::class)->willReturn($validatorClassMetadata)->shouldBeCalled();

        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            []
        );

        foreach ($types as $property => $iri) {
            $resultedPropertyMetadata = $validatorPropertyMetadataFactory->create(DummyIriWithValidationEntity::class, $property);
            $this->assertSame($iri, $resultedPropertyMetadata->getIri());
        }
    }

    public function testCreateWithPropertyLengthRestriction(): void
    {
        $validatorClassMetadata = new ClassMetadata(DummyValidatedEntity::class);
        (new AnnotationLoader(new AnnotationReader()))->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)
                                 ->willReturn($validatorClassMetadata)
                                 ->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $property = 'dummy';
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, $property, [])->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING))
        )->shouldBeCalled();

        $lengthRestrictions = new PropertySchemaLengthRestriction();
        $validatorPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(), $decoratedPropertyMetadataFactory->reveal(), [$lengthRestrictions]
        );

        $schema = $validatorPropertyMetadataFactory->create(DummyValidatedEntity::class, $property)->getSchema();
        $this->assertNotNull($schema);
        $this->assertArrayHasKey('minLength', $schema);
        $this->assertArrayHasKey('maxLength', $schema);
    }

    public function testCreateWithPropertyRegexRestriction(): void
    {
        $validatorClassMetadata = new ClassMetadata(DummyValidatedEntity::class);
        (new AnnotationLoader(new AnnotationReader()))->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)
                                 ->willReturn($validatorClassMetadata)
                                 ->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummy', [])->willReturn(
            new PropertyMetadata()
        )->shouldBeCalled();

        $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(), $decoratedPropertyMetadataFactory->reveal(),
            [new PropertySchemaRegexRestriction()]
        );

        $schema = $validationPropertyMetadataFactory->create(DummyValidatedEntity::class, 'dummy')->getSchema();
        $this->assertNotNull($schema);
        $this->assertArrayHasKey('pattern', $schema);
        $this->assertEquals('^(dummy)$', $schema['pattern']);
    }

    public function testCreateWithPropertyFormatRestriction(): void
    {
        $validatorClassMetadata = new ClassMetadata(DummyValidatedEntity::class);
        (new AnnotationLoader(new AnnotationReader()))->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedEntity::class)
                                 ->willReturn($validatorClassMetadata)
                                 ->shouldBeCalled();
        $formats = [
            'dummyEmail' => 'email',
            'dummyUuid' => 'uuid',
            'dummyIpv4' => 'ipv4',
            'dummyIpv6' => 'ipv6',
        ];

        foreach ($formats as $property => $format) {
            $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
            $decoratedPropertyMetadataFactory->create(DummyValidatedEntity::class, $property, [])->willReturn(
                new PropertyMetadata()
            )->shouldBeCalled();
            $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
                $validatorMetadataFactory->reveal(),
                $decoratedPropertyMetadataFactory->reveal(),
                [new PropertySchemaFormat()]
            );
            $schema = $validationPropertyMetadataFactory->create(DummyValidatedEntity::class, $property)->getSchema();
            $this->assertNotNull($schema);
            $this->assertArrayHasKey('format', $schema);
            $this->assertEquals($format, $schema['format']);
        }
    }

    public function testCreateWithSequentiallyConstraint(): void
    {
        if (!class_exists(Sequentially::class)) {
            $this->markTestSkipped();
        }

        $validatorClassMetadata = new ClassMetadata(DummySequentiallyValidatedEntity::class);
        (new AnnotationLoader(new AnnotationReader()))->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummySequentiallyValidatedEntity::class)
            ->willReturn($validatorClassMetadata)
            ->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummySequentiallyValidatedEntity::class, 'dummy', [])->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING))
        )->shouldBeCalled();
        $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            [new PropertySchemaLengthRestriction(), new PropertySchemaRegexRestriction()]
        );
        $schema = $validationPropertyMetadataFactory->create(DummySequentiallyValidatedEntity::class, 'dummy')->getSchema();

        $this->assertNotNull($schema);
        $this->assertArrayHasKey('minLength', $schema);
        $this->assertArrayHasKey('maxLength', $schema);
        $this->assertArrayHasKey('pattern', $schema);
    }

    public function testCreateWithAtLeastOneOfConstraint(): void
    {
        if (!class_exists(AtLeastOneOf::class)) {
            $this->markTestSkipped();
        }

        $validatorClassMetadata = new ClassMetadata(DummyAtLeastOneOfValidatedEntity::class);
        (new AnnotationLoader(new AnnotationReader()))->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyAtLeastOneOfValidatedEntity::class)
            ->willReturn($validatorClassMetadata)
            ->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyAtLeastOneOfValidatedEntity::class, 'dummy', [])->willReturn(
            new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING))
        )->shouldBeCalled();
        $restrictionsMetadata = [new PropertySchemaLengthRestriction(), new PropertySchemaRegexRestriction()];
        $restrictionsMetadata = [new PropertySchemaOneOfRestriction($restrictionsMetadata), new PropertySchemaLengthRestriction(), new PropertySchemaRegexRestriction()];
        $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            $restrictionsMetadata
        );
        $schema = $validationPropertyMetadataFactory->create(DummyAtLeastOneOfValidatedEntity::class, 'dummy')->getSchema();

        $this->assertNotNull($schema);
        $this->assertArrayHasKey('oneOf', $schema);
        $this->assertSame([
            ['pattern' => '^(.*#.*)$'],
            ['minLength' => 10],
        ], $schema['oneOf']);
    }
}
