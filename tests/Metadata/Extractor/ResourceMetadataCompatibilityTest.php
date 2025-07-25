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

namespace ApiPlatform\Tests\Metadata\Extractor;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Extractor\XmlResourceExtractor;
use ApiPlatform\Metadata\Extractor\YamlResourceExtractor;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\DeprecationMetadataTrait;
use ApiPlatform\Metadata\Resource\Factory\ExtractorResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Comment;
use ApiPlatform\Tests\Metadata\Extractor\Adapter\ResourceAdapterInterface;
use ApiPlatform\Tests\Metadata\Extractor\Adapter\XmlResourceAdapter;
use ApiPlatform\Tests\Metadata\Extractor\Adapter\YamlResourceAdapter;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * Ensures XML and YAML mappings are fully compatible with ApiResource.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class ResourceMetadataCompatibilityTest extends TestCase
{
    use DeprecationMetadataTrait;

    private const RESOURCE_CLASS = Comment::class;
    private const SHORT_NAME = 'Comment';
    private const DEFAULTS = [
        'route_prefix' => '/v1',
    ];
    private const FIXTURES = [
        null,
        [
            'uriTemplate' => '/users/{userId}/comments',
            'shortName' => self::SHORT_NAME,
            'description' => 'A list of Comments from User',
            'routePrefix' => '/api',
            'stateless' => true,
            'sunset' => '2021-01-01',
            'acceptPatch' => 'application/merge-patch+json',
            'status' => 200,
            'host' => 'example.com',
            'condition' => 'request.headers.get(\'User-Agent\') matches \{/firefox/i\'',
            'controller' => 'App\Controller\CommentController',
            'urlGenerationStrategy' => 1,
            'deprecationReason' => 'This resource is deprecated',
            'elasticsearch' => true,
            'messenger' => true,
            'input' => 'App\Dto\CommentInput',
            'output' => 'App\Dto\CommentOutut',
            'fetchPartial' => true,
            'forceEager' => true,
            'paginationClientEnabled' => true,
            'paginationClientItemsPerPage' => true,
            'paginationClientPartial' => true,
            'paginationEnabled' => true,
            'paginationFetchJoinCollection' => true,
            'paginationUseOutputWalkers' => true,
            'paginationItemsPerPage' => 42,
            'paginationMaximumItemsPerPage' => 200,
            'paginationPartial' => true,
            'paginationType' => 'page',
            'security' => 'is_granted(\'ROLE_USER\')',
            'securityMessage' => 'Sorry, you can\'t access this resource.',
            'securityPostDenormalize' => 'is_granted(\'ROLE_ADMIN\')',
            'securityPostDenormalizeMessage' => 'Sorry, you must an admin to access this resource.',
            'securityPostValidation' => 'is_granted(\'ROLE_OWNER\')',
            'securityPostValidationMessage' => 'Sorry, you must the owner of this resource to access it.',
            'queryParameterValidationEnabled' => true,
            'types' => ['someirischema', 'anotheririschema'],
            'formats' => [
                'json' => null,
                'jsonld' => null,
                'xls' => 'application/vnd.ms-excel',
            ],
            'inputFormats' => [
                'json' => 'application/merge-patch+json',
            ],
            'outputFormats' => [
                'json' => 'application/merge-patch+json',
            ],
            'uriVariables' => [
                'userId' => [
                    'fromClass' => Comment::class,
                    'fromProperty' => 'author',
                    'compositeIdentifier' => true,
                ],
            ],
            'defaults' => [
                'prout' => 'pouet',
            ],
            'requirements' => [
                'id' => '\d+',
            ],
            'options' => [
                'foo' => 'bar',
            ],
            'schemes' => ['http', 'https'],
            'cacheHeaders' => [
                'max_age' => 60,
                'shared_max_age' => 120,
                'vary' => ['Authorization', 'Accept-Language'],
            ],
            'normalizationContext' => [
                'groups' => 'comment:read',
            ],
            'denormalizationContext' => [
                'groups' => ['comment:write', 'comment:custom'],
            ],
            'hydraContext' => [
                'foo' => ['bar' => 'baz'],
            ],
            'openapiContext' => [
                'bar' => 'baz',
            ],
            'validationContext' => [
                'foo' => 'bar',
            ],
            'filters' => ['comment.custom_filter'],
            'order' => ['foo', 'bar'],
            'paginationViaCursor' => [
                'id' => 'DESC',
            ],
            'exceptionToStatus' => [
                'Symfony\Component\Serializer\Exception\ExceptionInterface' => 400,
            ],
            'extraProperties' => [
                'custom_property' => 'Lorem ipsum dolor sit amet',
                'another_custom_property' => [
                    'Lorem ipsum' => 'Dolor sit amet',
                ],
            ],
            'mercure' => true,
            'graphQlOperations' => [
                'mutations' => [
                    [
                        'args' => [
                            'foo' => [
                                'type' => 'custom',
                                'bar' => 'baz',
                            ],
                        ],
                        'shortName' => self::SHORT_NAME,
                        'description' => 'A list of Comments',
                        'class' => GetCollection::class,
                        'urlGenerationStrategy' => 0,
                        'deprecationReason' => 'I don\'t know',
                        'normalizationContext' => [
                            'groups' => 'comment:read_collection',
                        ],
                        'denormalizationContext' => [
                            'groups' => ['comment:write'],
                        ],
                        'validationContext' => [
                            'foo' => 'bar',
                        ],
                        'filters' => ['comment.another_custom_filter'],
                        'elasticsearch' => false,
                        'mercure' => [
                            'private' => true,
                        ],
                        'messenger' => 'input',
                        'input' => 'App\Dto\CreateCommentInput',
                        'output' => 'App\Dto\CommentCollectionOutut',
                        'order' => ['userId'],
                        'fetchPartial' => false,
                        'forceEager' => false,
                        'paginationClientEnabled' => false,
                        'paginationClientItemsPerPage' => false,
                        'paginationClientPartial' => false,
                        'paginationEnabled' => false,
                        'paginationFetchJoinCollection' => false,
                        'paginationUseOutputWalkers' => false,
                        'paginationItemsPerPage' => 54,
                        'paginationMaximumItemsPerPage' => 200,
                        'paginationPartial' => false,
                        'paginationType' => 'page',
                        'security' => 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')',
                        'securityMessage' => 'Sorry, you can\'t access this collection.',
                        'securityPostDenormalize' => 'is_granted(\'ROLE_CUSTOM_ADMIN\')',
                        'securityPostDenormalizeMessage' => 'Sorry, you must an admin to access this collection.',
                        'read' => true,
                        'deserialize' => false,
                        'validate' => false,
                        'write' => false,
                        'serialize' => true,
                        'priority' => 200,
                        'extraProperties' => [
                            'foo' => 'bar',
                            'custom_property' => 'Lorem ipsum dolor sit amet',
                            'another_custom_property' => [
                                'Lorem ipsum' => 'Dolor sit amet',
                            ],
                        ],
                    ],
                ],
                'queries' => [
                    [
                        'class' => Get::class,
                    ],
                ],
                'subscriptions' => [
                    [
                        'class' => Post::class,
                    ],
                ],
            ],
            'operations' => [
                [
                    'name' => 'custom_operation_name',
                    'method' => 'GET',
                    'uriTemplate' => '/users/{userId}/comments.{_format}',
                    'shortName' => self::SHORT_NAME,
                    'description' => 'A list of Comments',
                    'types' => ['Comment'],
                    'formats' => [
                        'json' => null,
                        'jsonld' => null,
                        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ],
                    'inputFormats' => [
                        'jsonld' => 'application/merge-patch+json+ld',
                    ],
                    'outputFormats' => [
                        'jsonld' => 'application/merge-patch+json+ld',
                    ],
                    'uriVariables' => [
                        'userId' => [
                            'fromClass' => Comment::class,
                            'fromProperty' => 'author',
                            'compositeIdentifier' => true,
                        ],
                    ],
                    'routePrefix' => '/foo/api',
                    'defaults' => [
                        '_bar' => '_foo',
                    ],
                    'requirements' => [
                        'userId' => '\d+',
                    ],
                    'options' => [
                        'bar' => 'baz',
                    ],
                    'stateless' => false,
                    'sunset' => '2021-12-01',
                    'acceptPatch' => 'text/example;charset=utf-8',
                    'status' => 204,
                    'host' => 'api-platform.com',
                    'schemes' => ['https'],
                    'condition' => 'request.headers.has(\'Accept\')',
                    'controller' => 'App\Controller\CustomController',
                    'class' => GetCollection::class,
                    'urlGenerationStrategy' => 0,
                    'deprecationReason' => 'I don\'t know',
                    'cacheHeaders' => [
                        'max_age' => 60,
                        'shared_max_age' => 120,
                        'vary' => ['Authorization', 'Accept-Language', 'Accept'],
                    ],
                    'normalizationContext' => [
                        'groups' => 'comment:read_collection',
                    ],
                    'denormalizationContext' => [
                        'groups' => ['comment:write'],
                    ],
                    'hydraContext' => [
                        'foo' => ['bar' => 'baz'],
                    ],
                    'openapiContext' => [
                        'bar' => 'baz',
                    ],
                    'validationContext' => [
                        'foo' => 'bar',
                    ],
                    'filters' => ['comment.another_custom_filter'],
                    'elasticsearch' => false,
                    'mercure' => [
                        'private' => true,
                    ],
                    'messenger' => 'input',
                    'input' => 'App\Dto\CreateCommentInput',
                    'output' => 'App\Dto\CommentCollectionOutut',
                    'order' => ['userId'],
                    'fetchPartial' => false,
                    'forceEager' => false,
                    'paginationClientEnabled' => false,
                    'paginationClientItemsPerPage' => false,
                    'paginationClientPartial' => false,
                    'paginationViaCursor' => [
                        'userId' => 'DESC',
                    ],
                    'paginationEnabled' => false,
                    'paginationFetchJoinCollection' => false,
                    'paginationUseOutputWalkers' => false,
                    'paginationItemsPerPage' => 54,
                    'paginationMaximumItemsPerPage' => 200,
                    'paginationPartial' => false,
                    'paginationType' => 'page',
                    'security' => 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')',
                    'securityMessage' => 'Sorry, you can\'t access this collection.',
                    'securityPostDenormalize' => 'is_granted(\'ROLE_CUSTOM_ADMIN\')',
                    'securityPostDenormalizeMessage' => 'Sorry, you must an admin to access this collection.',
                    'exceptionToStatus' => [
                        'Symfony\Component\Serializer\Exception\ExceptionInterface' => 404,
                    ],
                    'queryParameterValidationEnabled' => false,
                    'read' => true,
                    'deserialize' => false,
                    'validate' => false,
                    'write' => false,
                    'serialize' => true,
                    'priority' => 200,
                    'extraProperties' => [
                        'foo' => 'bar',
                        'custom_property' => 'Lorem ipsum dolor sit amet',
                        'another_custom_property' => [
                            'Lorem ipsum' => 'Dolor sit amet',
                        ],
                    ],
                ],
                [
                    'uriTemplate' => '/users/{userId}/comments/{commentId}.{_format}',
                    'class' => Get::class,
                    'uriVariables' => [
                        'userId' => [
                            'fromClass' => Comment::class,
                            'fromProperty' => 'author',
                            'compositeIdentifier' => true,
                        ],
                        'commentId' => [Comment::class, 'id'],
                    ],
                ],
            ],
        ],
    ];
    private const BASE = [
        'shortName',
        'description',
        'urlGenerationStrategy',
        'deprecationReason',
        'elasticsearch',
        'messenger',
        'mercure',
        'input',
        'output',
        'fetchPartial',
        'forceEager',
        'paginationClientEnabled',
        'paginationClientItemsPerPage',
        'paginationClientPartial',
        'paginationEnabled',
        'paginationFetchJoinCollection',
        'paginationUseOutputWalkers',
        'paginationItemsPerPage',
        'paginationMaximumItemsPerPage',
        'paginationPartial',
        'paginationType',
        'processor',
        'provider',
        'security',
        'securityMessage',
        'securityPostDenormalize',
        'securityPostDenormalizeMessage',
        'securityPostValidation',
        'securityPostValidationMessage',
        'normalizationContext',
        'denormalizationContext',
        'validationContext',
        'filters',
        'order',
        'extraProperties',
    ];
    private const EXTENDED_BASE = [
        'uriTemplate',
        'routePrefix',
        'stateless',
        'sunset',
        'acceptPatch',
        'status',
        'host',
        'condition',
        'controller',
        'queryParameterValidationEnabled',
        'exceptionToStatus',
        'types',
        'formats',
        'inputFormats',
        'outputFormats',
        'uriVariables',
        'defaults',
        'requirements',
        'options',
        'schemes',
        'cacheHeaders',
        'hydraContext',
        'openapiContext',
        'paginationViaCursor',
    ];

    /**
     * @dataProvider getExtractors
     */
    public function testValidMetadata(string $extractorClass, ResourceAdapterInterface $adapter): void
    {
        $reflClass = new \ReflectionClass(ApiResource::class);
        $parameters = $reflClass->getConstructor()->getParameters();

        try {
            $extractor = new $extractorClass($adapter(self::RESOURCE_CLASS, $parameters, self::FIXTURES));
            $factory = new ExtractorResourceMetadataCollectionFactory($extractor, null, self::DEFAULTS);
            $collection = $factory->create(self::RESOURCE_CLASS);
        } catch (\Exception $exception) {
            throw new AssertionFailedError('Failed asserting that the schema is valid according to '.ApiResource::class, 0, $exception);
        }

        $this->assertEquals(new ResourceMetadataCollection(self::RESOURCE_CLASS, $this->buildApiResources()), $collection);
    }

    public function getExtractors(): array
    {
        return [
            [XmlResourceExtractor::class, new XmlResourceAdapter()],
            [YamlResourceExtractor::class, new YamlResourceAdapter()],
        ];
    }

    /**
     * @return ApiResource[]
     */
    private function buildApiResources(): array
    {
        $resources = [];

        foreach (self::FIXTURES as $fixtures) {
            $resource = (new ApiResource())->withClass(self::RESOURCE_CLASS)->withShortName(self::SHORT_NAME);

            if (null === $fixtures) {
                // Build default operations
                $operations = [];
                foreach ([new Get(), new GetCollection(), new Post(), new Put(), new Patch(), new Delete()] as $operation) {
                    $operationName = sprintf('_api_%s_%s%s', $resource->getShortName(), strtolower($operation->getMethod()), $operation instanceof CollectionOperationInterface ? '_collection' : '');
                    $operations[$operationName] = $this->getOperationWithDefaults($resource, $operation)->withName($operationName);
                }

                $resources[] = $resource->withOperations(new Operations($operations));

                continue;
            }

            foreach ($fixtures as $parameter => $value) {
                if (method_exists($this, 'with'.ucfirst($parameter))) {
                    $value = $this->{'with'.ucfirst($parameter)}($value, $fixtures);
                }

                if (method_exists($resource, 'with'.ucfirst($parameter))) {
                    $resource = $resource->{'with'.ucfirst($parameter)}($value, $fixtures);
                    continue;
                }

                throw new \RuntimeException(sprintf('Unknown ApiResource parameter "%s".', $parameter));
            }

            $resources[] = $resource;
        }

        return $resources;
    }

    private function withUriVariables(array $values): array
    {
        $uriVariables = [];
        foreach ($values as $parameterName => $value) {
            if (\is_string($value)) {
                $uriVariables[$value] = $value;
                continue;
            }

            if (isset($value['fromClass']) || isset($value[0])) {
                $uriVariables[$parameterName]['from_class'] = $value['fromClass'] ?? $value[0];
            }
            if (isset($value['fromProperty']) || isset($value[1])) {
                $uriVariables[$parameterName]['from_property'] = $value['fromProperty'] ?? $value[1];
            }
            if (isset($value['toClass'])) {
                $uriVariables[$parameterName]['to_class'] = $value['toClass'];
            }
            if (isset($value['toProperty'])) {
                $uriVariables[$parameterName]['to_property'] = $value['toProperty'];
            }
            if (isset($value['identifiers'])) {
                $uriVariables[$parameterName]['identifiers'] = $value['identifiers'];
            }
            if (isset($value['compositeIdentifier'])) {
                $uriVariables[$parameterName]['composite_identifier'] = $value['compositeIdentifier'];
            }
        }

        return $uriVariables;
    }

    private function withOperations(array $values, ?array $fixtures): Operations
    {
        $operations = [];
        foreach ($values as $value) {
            $class = $value['class'] ?? HttpOperation::class;
            unset($value['class']);
            $operation = (new $class())->withClass(self::RESOURCE_CLASS);

            foreach (array_merge(self::BASE, self::EXTENDED_BASE) as $parameter) {
                if ((!\array_key_exists($parameter, $value) || null === $value[$parameter]) && isset($fixtures[$parameter])) {
                    $value[$parameter] = $fixtures[$parameter];
                }
            }

            foreach ($value as $parameter => $parameterValue) {
                if (method_exists($this, 'with'.ucfirst($parameter))) {
                    $parameterValue = $this->{'with'.ucfirst($parameter)}($parameterValue);
                }

                if (method_exists($operation, 'with'.ucfirst($parameter))) {
                    $operation = $operation->{'with'.ucfirst($parameter)}($parameterValue);
                    continue;
                }

                throw new \RuntimeException(sprintf('Unknown Operation parameter "%s".', $parameter));
            }

            if (null === $operation->getName()) {
                $operation = $operation->withName(sprintf('_api_%s_%s%s', $operation->getUriTemplate() ?: $operation->getShortName(), strtolower($operation->getMethod()), $operation instanceof CollectionOperationInterface ? '_collection' : ''));
            }
            $operations[$operation->getName()] = $operation;
        }

        return new Operations($operations);
    }

    private function withGraphQlOperations(array $values, ?array $fixtures): array
    {
        $operations = [];
        foreach ($values as $type => $graphQlOperations) {
            switch ($type) {
                default:
                case 'queries':
                    $class = Query::class;
                    break;
                case 'mutations':
                    $class = Mutation::class;
                    break;
                case 'subscriptions':
                    $class = Subscription::class;
                    break;
            }

            foreach ($graphQlOperations as $value) {
                $operation = new $class();

                foreach (self::BASE as $parameter) {
                    if ((!\array_key_exists($parameter, $value) || null === $value[$parameter]) && isset($fixtures[$parameter])) {
                        $value[$parameter] = $fixtures[$parameter];
                    }
                }

                foreach ($value as $parameter => $parameterValue) {
                    if (method_exists($this, 'with'.ucfirst($parameter))) {
                        $parameterValue = $this->{'with'.ucfirst($parameter)}($parameterValue);
                    }

                    if (method_exists($operation, 'with'.ucfirst($parameter))) {
                        $operation = $operation->{'with'.ucfirst($parameter)}($parameterValue);
                        continue;
                    }

                    throw new \RuntimeException(sprintf('Unknown GraphQlOperation parameter "%s".', $parameter));
                }

                $operations[] = $operation;
            }
        }

        return $operations;
    }

    private function getOperationWithDefaults(ApiResource $resource, HttpOperation $operation): HttpOperation
    {
        // Inherit from resource defaults
        foreach (get_class_methods($resource) as $methodName) {
            if (!str_starts_with($methodName, 'get')) {
                continue;
            }

            if (!method_exists($operation, $methodName) || null !== $operation->{$methodName}()) {
                continue;
            }

            if (null === ($value = $resource->{$methodName}())) {
                continue;
            }

            $operation = $operation->{'with'.substr($methodName, 3)}($value);
        }

        $operation = $operation->withExtraProperties(array_merge(
            $resource->getExtraProperties(),
            $operation->getExtraProperties()
        ));

        // Add global defaults attributes to the operation
        $operation = $this->addGlobalDefaults($operation);

        if ($operation->getRouteName()) {
            /** @var HttpOperation $operation */
            $operation = $operation->withName($operation->getRouteName());
        }

        // Check for name conflict
        if ($operation->getName() && null !== ($operations = $resource->getOperations())) {
            if (!$operations->has($operation->getName())) {
                return $operation;
            }

            /** @var HttpOperation $operation */
            $operation = $operation->withName('');
        }

        return $operation;
    }

    private function addGlobalDefaults(HttpOperation $operation): HttpOperation
    {
        if (!$this->camelCaseToSnakeCaseNameConverter) {
            $this->camelCaseToSnakeCaseNameConverter = new CamelCaseToSnakeCaseNameConverter();
        }

        $extraProperties = [];
        foreach (self::DEFAULTS as $key => $value) {
            $upperKey = ucfirst($this->camelCaseToSnakeCaseNameConverter->denormalize($key));
            $getter = 'get'.$upperKey;

            if (!method_exists($operation, $getter)) {
                if (!isset($extraProperties[$key])) {
                    $extraProperties[$key] = $value;
                }

                continue;
            }

            $currentValue = $operation->{$getter}();

            /* @phpstan-ignore-next-line */
            if (\is_array($currentValue) && $currentValue && \is_array($value) && $value) {
                $operation = $operation->{'with'.$upperKey}(array_merge($value, $currentValue));
            }

            if (null !== $currentValue) {
                continue;
            }

            $operation = $operation->{'with'.$upperKey}($value);
        }

        return $operation->withExtraProperties(array_merge($extraProperties, $operation->getExtraProperties()));
    }
}
