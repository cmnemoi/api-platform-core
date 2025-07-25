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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A legacy resource with a relation that has a custom data provider.
 *
 * @ApiResource
 *
 * @ORM\Entity
 */
class Issue5094Resource
{
    /**
     * @var int|null The id
     *
     * @ORM\Column(type="integer")
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Issue5094Relation
     */
    public $relation;

    public function getId(): ?int
    {
        return $this->id;
    }
}
