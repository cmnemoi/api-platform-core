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

namespace ApiPlatform\Tests\JsonSchema;

use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\JsonSchema\TypeFactory;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\PropertyInfo\Type;

class TypeFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider typeProvider
     */
    public function testGetType(array $schema, Type $type): void
    {
        $typeFactory = new TypeFactory();
        $this->assertEquals($schema, $typeFactory->getType($type, 'json', null, null, new Schema(Schema::VERSION_OPENAPI)));
    }

    public function typeProvider(): iterable
    {
        yield [['type' => 'integer'], new Type(Type::BUILTIN_TYPE_INT)];
        yield [['nullable' => true, 'type' => 'integer'], new Type(Type::BUILTIN_TYPE_INT, true)];
        yield [['type' => 'number'], new Type(Type::BUILTIN_TYPE_FLOAT)];
        yield [['nullable' => true, 'type' => 'number'], new Type(Type::BUILTIN_TYPE_FLOAT, true)];
        yield [['type' => 'boolean'], new Type(Type::BUILTIN_TYPE_BOOL)];
        yield [['nullable' => true, 'type' => 'boolean'], new Type(Type::BUILTIN_TYPE_BOOL, true)];
        yield [['type' => 'string'], new Type(Type::BUILTIN_TYPE_STRING)];
        yield [['nullable' => true, 'type' => 'string'], new Type(Type::BUILTIN_TYPE_STRING, true)];
        yield [['type' => 'string'], new Type(Type::BUILTIN_TYPE_OBJECT)];
        yield [['nullable' => true, 'type' => 'string'], new Type(Type::BUILTIN_TYPE_OBJECT, true)];
        yield [['type' => 'string', 'format' => 'date-time'], new Type(Type::BUILTIN_TYPE_OBJECT, false, \DateTimeImmutable::class)];
        yield [['nullable' => true, 'type' => 'string', 'format' => 'date-time'], new Type(Type::BUILTIN_TYPE_OBJECT, true, \DateTimeImmutable::class)];
        yield [['type' => 'string', 'format' => 'duration'], new Type(Type::BUILTIN_TYPE_OBJECT, false, \DateInterval::class)];
        yield [['type' => 'string', 'format' => 'binary'], new Type(Type::BUILTIN_TYPE_OBJECT, false, \SplFileInfo::class)];
        yield [['type' => 'string', 'format' => 'iri-reference'], new Type(Type::BUILTIN_TYPE_OBJECT, false, Dummy::class)];
        yield [['nullable' => true, 'type' => 'string', 'format' => 'iri-reference'], new Type(Type::BUILTIN_TYPE_OBJECT, true, Dummy::class)];
        yield [['type' => 'array', 'items' => ['type' => 'string']], new Type(Type::BUILTIN_TYPE_STRING, false, null, true)];
        yield 'array can be itself nullable' => [
            ['nullable' => true, 'type' => 'array', 'items' => ['type' => 'string']],
            new Type(Type::BUILTIN_TYPE_STRING, true, null, true),
        ];

        yield 'array can contain nullable values' => [
            [
                'type' => 'array',
                'items' => [
                    'nullable' => true,
                    'type' => 'string',
                ],
            ],
            new Type(Type::BUILTIN_TYPE_STRING, false, null, true, null, new Type(Type::BUILTIN_TYPE_STRING, true, null, false)),
        ];

        yield 'map with string keys becomes an object' => [
            ['type' => 'object', 'additionalProperties' => ['type' => 'string']],
            new Type(
                Type::BUILTIN_TYPE_STRING,
                false,
                null,
                true,
                new Type(Type::BUILTIN_TYPE_STRING, false, null, false)
            ),
        ];

        yield 'nullable map with string keys becomes a nullable object' => [
            [
                'nullable' => true,
                'type' => 'object',
                'additionalProperties' => ['type' => 'string'],
            ],
            new Type(
                Type::BUILTIN_TYPE_STRING,
                true,
                null,
                true,
                new Type(Type::BUILTIN_TYPE_STRING, false, null, false),
                new Type(Type::BUILTIN_TYPE_STRING, false, null, false)
            ),
        ];

        yield 'map value type will be considered' => [
            ['type' => 'object', 'additionalProperties' => ['type' => 'integer']],
            new Type(
                Type::BUILTIN_TYPE_ARRAY,
                false,
                null,
                true,
                new Type(Type::BUILTIN_TYPE_STRING, false, null, false),
                new Type(Type::BUILTIN_TYPE_INT, false, null, false)
            ),
        ];

        yield 'map value type nullability will be considered' => [
            [
                'type' => 'object',
                'additionalProperties' => [
                    'nullable' => true,
                    'type' => 'integer',
                ],
            ],
            new Type(
                Type::BUILTIN_TYPE_ARRAY,
                false,
                null,
                true,
                new Type(Type::BUILTIN_TYPE_STRING, false, null, false),
                new Type(Type::BUILTIN_TYPE_INT, true, null, false)
            ),
        ];

        yield 'nullable map can contain nullable values' => [
            [
                'nullable' => true,
                'type' => 'object',
                'additionalProperties' => [
                    'nullable' => true,
                    'type' => 'integer',
                ],
            ],
            new Type(
                Type::BUILTIN_TYPE_ARRAY,
                true,
                null,
                true,
                new Type(Type::BUILTIN_TYPE_STRING, false, null, false),
                new Type(Type::BUILTIN_TYPE_INT, true, null, false)
            ),
        ];
    }

    /**
     * @dataProvider jsonSchemaTypeProvider
     */
    public function testGetTypeWithJsonSchemaSyntax(array $schema, Type $type): void
    {
        $typeFactory = new TypeFactory();
        $this->assertEquals($schema, $typeFactory->getType($type, 'json', null, null, new Schema(Schema::VERSION_JSON_SCHEMA)));
    }

    public function jsonSchemaTypeProvider(): iterable
    {
        yield [['type' => 'integer'], new Type(Type::BUILTIN_TYPE_INT)];
        yield [['type' => ['integer', 'null']], new Type(Type::BUILTIN_TYPE_INT, true)];
        yield [['type' => 'number'], new Type(Type::BUILTIN_TYPE_FLOAT)];
        yield [['type' => ['number', 'null']], new Type(Type::BUILTIN_TYPE_FLOAT, true)];
        yield [['type' => 'boolean'], new Type(Type::BUILTIN_TYPE_BOOL)];
        yield [['type' => ['boolean', 'null']], new Type(Type::BUILTIN_TYPE_BOOL, true)];
        yield [['type' => 'string'], new Type(Type::BUILTIN_TYPE_STRING)];
        yield [['type' => ['string', 'null']], new Type(Type::BUILTIN_TYPE_STRING, true)];
        yield [['type' => 'string'], new Type(Type::BUILTIN_TYPE_OBJECT)];
        yield [['type' => ['string', 'null']], new Type(Type::BUILTIN_TYPE_OBJECT, true)];
        yield [['type' => 'string', 'format' => 'date-time'], new Type(Type::BUILTIN_TYPE_OBJECT, false, \DateTimeImmutable::class)];
        yield [['type' => ['string', 'null'], 'format' => 'date-time'], new Type(Type::BUILTIN_TYPE_OBJECT, true, \DateTimeImmutable::class)];
        yield [['type' => 'string', 'format' => 'duration'], new Type(Type::BUILTIN_TYPE_OBJECT, false, \DateInterval::class)];
        yield [['type' => 'string', 'format' => 'binary'], new Type(Type::BUILTIN_TYPE_OBJECT, false, \SplFileInfo::class)];
        yield [['type' => 'string', 'format' => 'iri-reference'], new Type(Type::BUILTIN_TYPE_OBJECT, false, Dummy::class)];
        yield [['type' => ['string', 'null'], 'format' => 'iri-reference'], new Type(Type::BUILTIN_TYPE_OBJECT, true, Dummy::class)];
        yield [['type' => 'array', 'items' => ['type' => 'string']], new Type(Type::BUILTIN_TYPE_STRING, false, null, true)];
        yield 'array can be itself nullable' => [
            ['type' => ['array', 'null'], 'items' => ['type' => 'string']],
            new Type(Type::BUILTIN_TYPE_STRING, true, null, true),
        ];

        yield 'array can contain nullable values' => [
            [
                'type' => 'array',
                'items' => [
                    'type' => ['string', 'null'],
                ],
            ],
            new Type(Type::BUILTIN_TYPE_STRING, false, null, true, null, new Type(Type::BUILTIN_TYPE_STRING, true, null, false)),
        ];

        yield 'map with string keys becomes an object' => [
            ['type' => 'object', 'additionalProperties' => ['type' => 'string']],
            new Type(
                Type::BUILTIN_TYPE_STRING,
                false,
                null,
                true,
                new Type(Type::BUILTIN_TYPE_STRING, false, null, false)
            ),
        ];

        yield 'nullable map with string keys becomes a nullable object' => [
            [
                'type' => ['object', 'null'],
                'additionalProperties' => ['type' => 'string'],
            ],
            new Type(
                Type::BUILTIN_TYPE_STRING,
                true,
                null,
                true,
                new Type(Type::BUILTIN_TYPE_STRING, false, null, false),
                new Type(Type::BUILTIN_TYPE_STRING, false, null, false)
            ),
        ];

        yield 'map value type will be considered' => [
            ['type' => 'object', 'additionalProperties' => ['type' => 'integer']],
            new Type(
                Type::BUILTIN_TYPE_ARRAY,
                false,
                null,
                true,
                new Type(Type::BUILTIN_TYPE_STRING, false, null, false),
                new Type(Type::BUILTIN_TYPE_INT, false, null, false)
            ),
        ];

        yield 'map value type nullability will be considered' => [
            [
                'type' => 'object',
                'additionalProperties' => [
                    'type' => ['integer', 'null'],
                ],
            ],
            new Type(
                Type::BUILTIN_TYPE_ARRAY,
                false,
                null,
                true,
                new Type(Type::BUILTIN_TYPE_STRING, false, null, false),
                new Type(Type::BUILTIN_TYPE_INT, true, null, false)
            ),
        ];

        yield 'nullable map can contain nullable values' => [
            [
                'type' => ['object', 'null'],
                'additionalProperties' => [
                    'type' => ['integer', 'null'],
                ],
            ],
            new Type(
                Type::BUILTIN_TYPE_ARRAY,
                true,
                null,
                true,
                new Type(Type::BUILTIN_TYPE_STRING, false, null, false),
                new Type(Type::BUILTIN_TYPE_INT, true, null, false)
            ),
        ];
    }

    /** @dataProvider openAPIV2TypeProvider */
    public function testGetTypeWithOpenAPIV2Syntax(array $schema, Type $type): void
    {
        $typeFactory = new TypeFactory();
        $this->assertSame($schema, $typeFactory->getType($type, 'json', null, null, new Schema(Schema::VERSION_SWAGGER)));
    }

    public function openAPIV2TypeProvider(): iterable
    {
        yield [['type' => 'integer'], new Type(Type::BUILTIN_TYPE_INT)];
        yield [['type' => 'integer'], new Type(Type::BUILTIN_TYPE_INT, true)];
        yield [['type' => 'number'], new Type(Type::BUILTIN_TYPE_FLOAT)];
        yield [['type' => 'number'], new Type(Type::BUILTIN_TYPE_FLOAT, true)];
        yield [['type' => 'boolean'], new Type(Type::BUILTIN_TYPE_BOOL)];
        yield [['type' => 'boolean'], new Type(Type::BUILTIN_TYPE_BOOL, true)];
        yield [['type' => 'string'], new Type(Type::BUILTIN_TYPE_STRING)];
        yield [['type' => 'string'], new Type(Type::BUILTIN_TYPE_STRING, true)];
        yield [['type' => 'string'], new Type(Type::BUILTIN_TYPE_OBJECT)];
        yield [['type' => 'string'], new Type(Type::BUILTIN_TYPE_OBJECT, true)];
        yield [['type' => 'string', 'format' => 'date-time'], new Type(Type::BUILTIN_TYPE_OBJECT, false, \DateTimeImmutable::class)];
        yield [['type' => 'string', 'format' => 'date-time'], new Type(Type::BUILTIN_TYPE_OBJECT, true, \DateTimeImmutable::class)];
        yield [['type' => 'string', 'format' => 'duration'], new Type(Type::BUILTIN_TYPE_OBJECT, false, \DateInterval::class)];
        yield [['type' => 'string', 'format' => 'binary'], new Type(Type::BUILTIN_TYPE_OBJECT, false, \SplFileInfo::class)];
        yield [['type' => 'string', 'format' => 'iri-reference'], new Type(Type::BUILTIN_TYPE_OBJECT, false, Dummy::class)];
        yield [['type' => 'string', 'format' => 'iri-reference'], new Type(Type::BUILTIN_TYPE_OBJECT, true, Dummy::class)];
        yield [['type' => 'array', 'items' => ['type' => 'string']], new Type(Type::BUILTIN_TYPE_STRING, false, null, true)];
        yield 'array can be itself nullable, but ignored in OpenAPI V2' => [
            ['type' => 'array', 'items' => ['type' => 'string']],
            new Type(Type::BUILTIN_TYPE_STRING, true, null, true),
        ];

        yield 'array can contain nullable values, but ignored in OpenAPI V2' => [
            [
                'type' => 'array',
                'items' => ['type' => 'string'],
            ],
            new Type(Type::BUILTIN_TYPE_STRING, false, null, true, null, new Type(Type::BUILTIN_TYPE_STRING, true, null, false)),
        ];

        yield 'map with string keys becomes an object' => [
            ['type' => 'object', 'additionalProperties' => ['type' => 'string']],
            new Type(
                Type::BUILTIN_TYPE_STRING,
                false,
                null,
                true,
                new Type(Type::BUILTIN_TYPE_STRING, false, null, false)
            ),
        ];

        yield 'nullable map with string keys becomes a nullable object, but ignored in OpenAPI V2' => [
            [
                'type' => 'object',
                'additionalProperties' => ['type' => 'string'],
            ],
            new Type(
                Type::BUILTIN_TYPE_STRING,
                true,
                null,
                true,
                new Type(Type::BUILTIN_TYPE_STRING, false, null, false),
                new Type(Type::BUILTIN_TYPE_STRING, false, null, false)
            ),
        ];

        yield 'map value type will be considered' => [
            ['type' => 'object', 'additionalProperties' => ['type' => 'integer']],
            new Type(
                Type::BUILTIN_TYPE_ARRAY,
                false,
                null,
                true,
                new Type(Type::BUILTIN_TYPE_STRING, false, null, false),
                new Type(Type::BUILTIN_TYPE_INT, false, null, false)
            ),
        ];

        yield 'map value type nullability will be considered, but ignored in OpenAPI V2' => [
            [
                'type' => 'object',
                'additionalProperties' => ['type' => 'integer'],
            ],
            new Type(
                Type::BUILTIN_TYPE_ARRAY,
                false,
                null,
                true,
                new Type(Type::BUILTIN_TYPE_STRING, false, null, false),
                new Type(Type::BUILTIN_TYPE_INT, true, null, false)
            ),
        ];

        yield 'nullable map can contain nullable values, but ignored in OpenAPI V2' => [
            [
                'type' => 'object',
                'additionalProperties' => ['type' => 'integer'],
            ],
            new Type(
                Type::BUILTIN_TYPE_ARRAY,
                true,
                null,
                true,
                new Type(Type::BUILTIN_TYPE_STRING, false, null, false),
                new Type(Type::BUILTIN_TYPE_INT, true, null, false)
            ),
        ];
    }

    public function testGetClassType(): void
    {
        $schemaFactoryProphecy = $this->prophesize(SchemaFactoryInterface::class);

        $schemaFactoryProphecy->buildSchema(Dummy::class, 'jsonld', Schema::TYPE_OUTPUT, null, Argument::type(Schema::class), ['foo' => 'bar'], false)->will(function (array $args) {
            $args[4]['$ref'] = 'ref';

            return $args[4];
        });

        $typeFactory = new TypeFactory();
        $typeFactory->setSchemaFactory($schemaFactoryProphecy->reveal());

        $this->assertSame(['$ref' => 'ref'], $typeFactory->getType(new Type(Type::BUILTIN_TYPE_OBJECT, false, Dummy::class), 'jsonld', true, ['foo' => 'bar'], new Schema()));
    }

    /** @dataProvider classTypeWithNullabilityDataProvider */
    public function testGetClassTypeWithNullability(array $expected, SchemaFactoryInterface $schemaFactory, Schema $schema): void
    {
        $typeFactory = new TypeFactory();
        $typeFactory->setSchemaFactory($schemaFactory);

        self::assertSame(
            $expected,
            $typeFactory->getType(new Type(Type::BUILTIN_TYPE_OBJECT, true, Dummy::class), 'jsonld', true, ['foo' => 'bar'], $schema)
        );
    }

    public function classTypeWithNullabilityDataProvider(): iterable
    {
        $schemaFactory = $this->createSchemaFactoryMock($schema = new Schema());

        yield 'JSON-Schema version' => [
            [
                'anyOf' => [
                    ['$ref' => 'the-ref-name'],
                    ['type' => 'null'],
                ],
            ],
            $schemaFactory,
            $schema,
        ];

        $schemaFactory = $this->createSchemaFactoryMock($schema = new Schema(Schema::VERSION_OPENAPI));

        yield 'OpenAPI < 3.1 version' => [
            [
                'anyOf' => [
                    ['$ref' => 'the-ref-name'],
                ],
                'nullable' => true,
            ],
            $schemaFactory,
            $schema,
        ];
    }

    private function createSchemaFactoryMock(Schema $schema): SchemaFactoryInterface
    {
        $schemaFactory = $this->createMock(SchemaFactoryInterface::class);

        $schemaFactory
            ->method('buildSchema')
            ->willReturnCallback(static function () use ($schema): Schema {
                $schema['$ref'] = 'the-ref-name';
                $schema['description'] = 'more stuff here';

                return $schema;
            });

        return $schemaFactory;
    }
}
