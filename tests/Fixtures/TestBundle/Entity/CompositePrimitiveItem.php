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
 * Composite Primitive Item.
 *
 * @ApiResource
 *
 * @ORM\Entity
 */
class CompositePrimitiveItem
{
    /**
     * @ORM\Id
     *
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @ORM\Id
     *
     * @ORM\Column(type="integer")
     */
    private $year;

    /**
     * @ORM\Column(type="text")
     */
    private $description;

    public function __construct(string $name, int $year)
    {
        $this->name = $name;
        $this->year = $year;
    }

    /**
     * Gets name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets year.
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * Sets description.
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * Gets description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}
