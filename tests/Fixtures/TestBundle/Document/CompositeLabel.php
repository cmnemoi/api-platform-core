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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Composite Label.
 *
 * @ApiResource
 *
 * @ODM\Document
 */
class CompositeLabel
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;

    /**
     * @ODM\Field(type="string", nullable=true)
     *
     * @Groups({"default"})
     */
    private $value;

    /**
     * Gets id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Gets value.
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Sets value.
     *
     * @param string|null $value the value to set
     */
    public function setValue($value = null)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return (string) $this->id;
    }
}
