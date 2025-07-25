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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Custom Normalized Dummy.
 *
 * @author Mikaël Labrut <labrut@gmail.com>
 *
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"output"}},
 *     "denormalization_context"={"groups"={"input"}}
 * })
 *
 * @ODM\Document
 */
class CustomNormalizedDummy
{
    /**
     * @var int|null The id
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     *
     * @Groups({"input", "output"})
     */
    private $id;

    /**
     * @var string|null The dummy name
     *
     * @ODM\Field
     *
     * @Assert\NotBlank
     *
     * @ApiProperty(iri="http://schema.org/name")
     *
     * @Groups({"input", "output"})
     */
    private $name;

    /**
     * @var string|null The dummy name alias
     *
     * @ODM\Field(nullable=true)
     *
     * @ApiProperty(iri="https://schema.org/alternateName")
     *
     * @Groups({"input", "output"})
     */
    private $alias;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * @param string $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    public function getPersonalizedAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * @param string $value
     */
    public function setPersonalizedAlias($value)
    {
        $this->alias = $value;
    }
}
