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

namespace ApiPlatform\Tests\Fixtures\TestBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Product as ProductDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Product;
use ApiPlatform\Tests\Fixtures\TestBundle\Model\ProductInterface;
use Doctrine\Persistence\ManagerRegistry;

class ProductItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private $managerRegistry;
    private $orm;

    public function __construct(ManagerRegistry $managerRegistry, bool $orm = true)
    {
        $this->managerRegistry = $managerRegistry;
        $this->orm = $orm;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return is_a($resourceClass, ProductInterface::class, true);
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        return $this->managerRegistry->getRepository($this->orm ? Product::class : ProductDocument::class)->findOneBy([
            'code' => $id,
        ]);
    }
}
