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

namespace ApiPlatform\Tests\Symfony\Validator\Metadata\Property;

use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaChoiceRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaCollectionRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaCountRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaFormat;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaGreaterThanOrEqualRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaGreaterThanRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaLengthRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaLessThanOrEqualRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaLessThanRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaOneOfRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRangeRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRegexRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaUniqueRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\ValidatorPropertyMetadataFactory;
use ApiPlatform\Tests\Fixtures\DummyAtLeastOneOfValidatedEntity;
use ApiPlatform\Tests\Fixtures\DummyCollectionValidatedEntity;
use ApiPlatform\Tests\Fixtures\DummyCompoundValidatedEntity;
use ApiPlatform\Tests\Fixtures\DummyCountValidatedEntity;
use ApiPlatform\Tests\Fixtures\DummyIriWithValidationEntity;
use ApiPlatform\Tests\Fixtures\DummyNumericValidatedEntity;
use ApiPlatform\Tests\Fixtures\DummyRangeValidatedEntity;
use ApiPlatform\Tests\Fixtures\DummySequentiallyValidatedEntity;
use ApiPlatform\Tests\Fixtures\DummyUniqueValidatedEntity;
use ApiPlatform\Tests\Fixtures\DummyValidatedChoiceEntity;
use ApiPlatform\Tests\Fixtures\DummyValidatedEntity;
use ApiPlatform\Tests\Fixtures\DummyValidatedHostnameEntity;
use ApiPlatform\Tests\Fixtures\DummyValidatedUlidEntity;
use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Validator\Constraints\AtLeastOneOf;
use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints\Hostname;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\Constraints\Ulid;
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
        $propertyMetadata = (new ApiProperty())->withDescription('A dummy')->withReadable(true)->withWritable(true);
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
        $propertyMetadata = (new ApiProperty())->withDescription('A dummy')->withReadable(true)->withWritable(true);
        $expectedPropertyMetadata = $propertyMetadata->withRequired(false);
        $expectedPropertyMetadata = $expectedPropertyMetadata->withTypes(['http://schema.org/Date']);

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
        $propertyMetadata = (new ApiProperty())->withDescription('A dummy')->withReadable(true)->withWritable(true)->withIdentifier(true);
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
        $propertyMetadata = (new ApiProperty())->withDescription('A dummy group')->withReadable(true)->withWritable(true);
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
        $propertyMetadata = (new ApiProperty())->withDescription('A dummy group')->withReadable(true)->withWritable(true);
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
        $propertyMetadata = (new ApiProperty())->withDescription('A dummy group')->withReadable(true)->withWritable(true);
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
        $propertyMetadata = (new ApiProperty())->withDescription('A dummy group')->withReadable(true)->withRequired(true)->withTypes(['foo:bar']);
        $expectedPropertyMetadata = (clone $propertyMetadata)->withTypes(['foo:bar', 'http://schema.org/Date']);

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
            $decoratedPropertyMetadataFactory->create(DummyIriWithValidationEntity::class, $property, [])->willReturn(new ApiProperty())->shouldBeCalled();
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
            $this->assertSame($iri, $resultedPropertyMetadata->getTypes()[0]);
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
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])
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
            new ApiProperty()
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

    /**
     * @dataProvider providePropertySchemaFormatCases
     */
    public function testCreateWithPropertyFormatRestriction(string $property, string $class, array $expectedSchema): void
    {
        $validatorClassMetadata = new ClassMetadata($class);
        (new AnnotationLoader(new AnnotationReader()))->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor($class)
                                 ->willReturn($validatorClassMetadata)
                                 ->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create($class, $property, [])->willReturn(
            new ApiProperty()
        )->shouldBeCalled();
        $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            [new PropertySchemaFormat()]
        );
        $schema = $validationPropertyMetadataFactory->create($class, $property)->getSchema();

        $this->assertSame($expectedSchema, $schema);
    }

    public function providePropertySchemaFormatCases(): \Generator
    {
        yield ['dummyEmail', DummyValidatedEntity::class, ['format' => 'email']];
        yield ['dummyUuid', DummyValidatedEntity::class, ['format' => 'uuid']];
        yield ['dummyIpv4', DummyValidatedEntity::class, ['format' => 'ipv4']];
        yield ['dummyIpv6', DummyValidatedEntity::class, ['format' => 'ipv6']];
        yield ['dummyUrl', DummyValidatedEntity::class, ['format' => 'uri']];
        if (class_exists(Ulid::class)) {
            yield ['dummyUlid', DummyValidatedUlidEntity::class, ['format' => 'ulid']];
        }
        if (class_exists(Hostname::class)) {
            yield ['dummyHostname', DummyValidatedHostnameEntity::class, ['format' => 'hostname']];
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
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])
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

    public function testCreateWithCompoundConstraint(): void
    {
        if (!class_exists(Compound::class)) {
            $this->markTestSkipped();
        }

        $validatorClassMetadata = new ClassMetadata(DummyCompoundValidatedEntity::class);
        (new AnnotationLoader(new AnnotationReader()))->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyCompoundValidatedEntity::class)
            ->willReturn($validatorClassMetadata)
            ->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyCompoundValidatedEntity::class, 'dummy', [])->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])
        )->shouldBeCalled();
        $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            [new PropertySchemaLengthRestriction(), new PropertySchemaRegexRestriction()]
        );
        $schema = $validationPropertyMetadataFactory->create(DummyCompoundValidatedEntity::class, 'dummy')->getSchema();

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
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])
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

    public function testCreateWithPropertyUniqueRestriction(): void
    {
        $validatorClassMetadata = new ClassMetadata(DummyUniqueValidatedEntity::class);
        (new AnnotationLoader(new AnnotationReader()))->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyUniqueValidatedEntity::class)
            ->willReturn($validatorClassMetadata)
            ->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyUniqueValidatedEntity::class, 'dummyItems', [])->willReturn(
            new ApiProperty()
        )->shouldBeCalled();

        $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            [new PropertySchemaUniqueRestriction()]
        );

        $schema = $validationPropertyMetadataFactory->create(DummyUniqueValidatedEntity::class, 'dummyItems')->getSchema();

        $this->assertSame(['uniqueItems' => true], $schema);
    }

    /**
     * @dataProvider provideRangeConstraintCases
     */
    public function testCreateWithRangeConstraint(Type $type, string $property, array $expectedSchema): void
    {
        $validatorClassMetadata = new ClassMetadata(DummyRangeValidatedEntity::class);
        (new AnnotationLoader(new AnnotationReader()))->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyRangeValidatedEntity::class)
            ->willReturn($validatorClassMetadata)
            ->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyRangeValidatedEntity::class, $property, [])->willReturn(
            (new ApiProperty())->withBuiltinTypes([$type])
        )->shouldBeCalled();
        $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            [new PropertySchemaRangeRestriction()]
        );
        $schema = $validationPropertyMetadataFactory->create(DummyRangeValidatedEntity::class, $property)->getSchema();

        $this->assertSame($expectedSchema, $schema);
    }

    public function provideRangeConstraintCases(): \Generator
    {
        yield 'min int' => ['type' => new Type(Type::BUILTIN_TYPE_INT), 'property' => 'dummyIntMin', 'expectedSchema' => ['minimum' => 1]];
        yield 'max int' => ['type' => new Type(Type::BUILTIN_TYPE_INT), 'property' => 'dummyIntMax', 'expectedSchema' => ['maximum' => 10]];
        yield 'min/max int' => ['type' => new Type(Type::BUILTIN_TYPE_INT), 'property' => 'dummyIntMinMax', 'expectedSchema' => ['minimum' => 1, 'maximum' => 10]];
        yield 'min float' => ['type' => new Type(Type::BUILTIN_TYPE_FLOAT), 'property' => 'dummyFloatMin', 'expectedSchema' => ['minimum' => 1.5]];
        yield 'max float' => ['type' => new Type(Type::BUILTIN_TYPE_FLOAT), 'property' => 'dummyFloatMax', 'expectedSchema' => ['maximum' => 10.5]];
        yield 'min/max float' => ['type' => new Type(Type::BUILTIN_TYPE_FLOAT), 'property' => 'dummyFloatMinMax', 'expectedSchema' => ['minimum' => 1.5, 'maximum' => 10.5]];
    }

    /**
     * @dataProvider provideChoiceConstraintCases
     */
    public function testCreateWithPropertyChoiceRestriction(ApiProperty $propertyMetadata, string $property, array $expectedSchema): void
    {
        $validatorClassMetadata = new ClassMetadata(DummyValidatedChoiceEntity::class);
        (new AnnotationLoader(new AnnotationReader()))->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyValidatedChoiceEntity::class)
            ->willReturn($validatorClassMetadata)
            ->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyValidatedChoiceEntity::class, $property, [])->willReturn(
            $propertyMetadata
        )->shouldBeCalled();

        $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(), $decoratedPropertyMetadataFactory->reveal(),
            [new PropertySchemaChoiceRestriction()]
        );

        $schema = $validationPropertyMetadataFactory->create(DummyValidatedChoiceEntity::class, $property)->getSchema();

        $this->assertSame($expectedSchema, $schema);
    }

    public function provideChoiceConstraintCases(): \Generator
    {
        yield 'single choice' => ['propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)]), 'property' => 'dummySingleChoice', 'expectedSchema' => ['enum' => ['a', 'b']]];
        yield 'single choice callback' => ['propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)]), 'property' => 'dummySingleChoiceCallback', 'expectedSchema' => ['enum' => ['a', 'b', 'c', 'd']]];
        yield 'multi choice' => ['propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)]), 'property' => 'dummyMultiChoice', 'expectedSchema' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a', 'b']]]];
        yield 'multi choice callback' => ['propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)]), 'property' => 'dummyMultiChoiceCallback', 'expectedSchema' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a', 'b', 'c', 'd']]]];
        yield 'multi choice min' => ['propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)]), 'property' => 'dummyMultiChoiceMin', 'expectedSchema' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a', 'b', 'c', 'd']], 'minItems' => 2]];
        yield 'multi choice max' => ['propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)]), 'property' => 'dummyMultiChoiceMax', 'expectedSchema' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a', 'b', 'c', 'd']], 'maxItems' => 4]];
        yield 'multi choice min/max' => ['propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)]), 'property' => 'dummyMultiChoiceMinMax', 'expectedSchema' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a', 'b', 'c', 'd']], 'minItems' => 2, 'maxItems' => 4]];
    }

    /**
     * @dataProvider provideCountConstraintCases
     */
    public function testCreateWithPropertyCountRestriction(string $property, array $expectedSchema): void
    {
        $validatorClassMetadata = new ClassMetadata(DummyCountValidatedEntity::class);
        (new AnnotationLoader(new AnnotationReader()))->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyCountValidatedEntity::class)
            ->willReturn($validatorClassMetadata)
            ->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyCountValidatedEntity::class, $property, [])->willReturn(
            new ApiProperty()
        )->shouldBeCalled();

        $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(), $decoratedPropertyMetadataFactory->reveal(),
            [new PropertySchemaCountRestriction()]
        );

        $schema = $validationPropertyMetadataFactory->create(DummyCountValidatedEntity::class, $property)->getSchema();

        $this->assertSame($expectedSchema, $schema);
    }

    public function provideCountConstraintCases(): \Generator
    {
        yield 'min' => ['property' => 'dummyMin', 'expectedSchema' => ['minItems' => 1]];
        yield 'max' => ['property' => 'dummyMax', 'expectedSchema' => ['maxItems' => 10]];
        yield 'min/max' => ['property' => 'dummyMinMax', 'expectedSchema' => ['minItems' => 1, 'maxItems' => 10]];
    }

    public function testCreateWithPropertyCollectionRestriction(): void
    {
        $validatorClassMetadata = new ClassMetadata(DummyCollectionValidatedEntity::class);
        (new AnnotationLoader(new AnnotationReader()))->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyCollectionValidatedEntity::class)
            ->willReturn($validatorClassMetadata)
            ->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyCollectionValidatedEntity::class, 'dummyData', [])->willReturn(
            (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_ARRAY)])
        )->shouldBeCalled();

        $lengthRestriction = new PropertySchemaLengthRestriction();
        $regexRestriction = new PropertySchemaRegexRestriction();
        $formatRestriction = new PropertySchemaFormat();
        $restrictionsMetadata = [
            $lengthRestriction,
            $regexRestriction,
            $formatRestriction,
            new PropertySchemaCollectionRestriction([
                $lengthRestriction,
                $regexRestriction,
                $formatRestriction,
                new PropertySchemaCollectionRestriction([
                    $lengthRestriction,
                    $regexRestriction,
                    $formatRestriction,
                ]),
            ]),
        ];

        $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(),
            $decoratedPropertyMetadataFactory->reveal(),
            $restrictionsMetadata
        );

        $schema = $validationPropertyMetadataFactory->create(DummyCollectionValidatedEntity::class, 'dummyData')->getSchema();

        $this->assertSame([
            'type' => 'object',
            'properties' => [
                'name' => [],
                'email' => ['format' => 'email'],
                'phone' => ['pattern' => '^([+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\./0-9]*)$'],
                'age' => [],
                'social' => [
                    'type' => 'object',
                    'properties' => [
                        'githubUsername' => [],
                    ],
                    'additionalProperties' => false,
                    'required' => ['githubUsername'],
                ],
            ],
            'additionalProperties' => true,
            'required' => ['name', 'email', 'social'],
        ], $schema);
    }

    /**
     * @dataProvider provideNumericConstraintCases
     */
    public function testCreateWithPropertyNumericRestriction(ApiProperty $propertyMetadata, string $property, array $expectedSchema): void
    {
        $validatorClassMetadata = new ClassMetadata(DummyNumericValidatedEntity::class);
        (new AnnotationLoader(new AnnotationReader()))->loadClassMetadata($validatorClassMetadata);

        $validatorMetadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $validatorMetadataFactory->getMetadataFor(DummyNumericValidatedEntity::class)
            ->willReturn($validatorClassMetadata)
            ->shouldBeCalled();

        $decoratedPropertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decoratedPropertyMetadataFactory->create(DummyNumericValidatedEntity::class, $property, [])->willReturn(
            $propertyMetadata
        )->shouldBeCalled();

        $validationPropertyMetadataFactory = new ValidatorPropertyMetadataFactory(
            $validatorMetadataFactory->reveal(), $decoratedPropertyMetadataFactory->reveal(),
            [
                new PropertySchemaGreaterThanOrEqualRestriction(),
                new PropertySchemaGreaterThanRestriction(),
                new PropertySchemaLessThanOrEqualRestriction(),
                new PropertySchemaLessThanRestriction(),
            ]
        );

        $schema = $validationPropertyMetadataFactory->create(DummyNumericValidatedEntity::class, $property)->getSchema();

        $this->assertSame($expectedSchema, $schema);
    }

    public function provideNumericConstraintCases(): \Generator
    {
        yield [
            'propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)]),
            'property' => 'greaterThanMe',
            'expectedSchema' => ['minimum' => 10, 'exclusiveMinimum' => true],
        ];

        yield [
            'propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_FLOAT)]),
            'property' => 'greaterThanOrEqualToMe',
            'expectedSchema' => ['minimum' => 10.99],
        ];

        yield [
            'propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)]),
            'property' => 'lessThanMe',
            'expectedSchema' => ['maximum' => 99, 'exclusiveMaximum' => true],
        ];

        yield [
            'propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_FLOAT)]),
            'property' => 'lessThanOrEqualToMe',
            'expectedSchema' => ['maximum' => 99.33],
        ];

        yield [
            'propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)]),
            'property' => 'positive',
            'expectedSchema' => ['minimum' => 0, 'exclusiveMinimum' => true],
        ];

        yield [
            'propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)]),
            'property' => 'positiveOrZero',
            'expectedSchema' => ['minimum' => 0],
        ];

        yield [
            'propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)]),
            'property' => 'negative',
            'expectedSchema' => ['maximum' => 0, 'exclusiveMaximum' => true],
        ];

        yield [
            'propertyMetadata' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)]),
            'property' => 'negativeOrZero',
            'expectedSchema' => ['maximum' => 0],
        ];
    }
}
