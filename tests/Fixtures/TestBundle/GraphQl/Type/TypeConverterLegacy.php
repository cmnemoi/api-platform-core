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

namespace ApiPlatform\Tests\Fixtures\TestBundle\GraphQl\Type;

use ApiPlatform\Core\GraphQl\Type\TypeConverterInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy as DummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use GraphQL\Type\Definition\Type as GraphQLType;
use Symfony\Component\PropertyInfo\Type;

/**
 * Converts a built-in type to its GraphQL equivalent.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class TypeConverterLegacy implements TypeConverterInterface
{
    private $defaultTypeConverter;

    public function __construct(TypeConverterInterface $defaultTypeConverter)
    {
        $this->defaultTypeConverter = $defaultTypeConverter;
    }

    public function convertType(Type $type, bool $input, ?string $queryName, ?string $mutationName, ?string $subscriptionName, string $resourceClass, string $rootResource, ?string $property, int $depth)
    {
        if ('dummyDate' === $property
            && \in_array($rootResource, [Dummy::class, DummyDocument::class], true)
            && Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType()
            && is_a($type->getClassName(), \DateTimeInterface::class, true)
        ) {
            return 'DateTime';
        }

        return $this->defaultTypeConverter->convertType($type, $input, $queryName, $mutationName, $subscriptionName, $resourceClass, $rootResource, $property, $depth);
    }

    public function resolveType(string $type): ?GraphQLType
    {
        return $this->defaultTypeConverter->resolveType($type);
    }
}
