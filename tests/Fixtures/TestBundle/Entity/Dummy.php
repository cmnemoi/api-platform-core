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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Dummy.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource(attributes={
 *     "filters"={
 *         "my_dummy.boolean",
 *         "my_dummy.date",
 *         "my_dummy.exists",
 *         "my_dummy.numeric",
 *         "my_dummy.order",
 *         "my_dummy.range",
 *         "my_dummy.search",
 *         "my_dummy.property"
 *     }
 * })
 *
 * @ORM\Entity
 */
class Dummy
{
    /**
     * @var int|null The id
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string The dummy name
     *
     * @ORM\Column
     *
     * @Assert\NotBlank
     *
     * @ApiProperty(iri="http://schema.org/name")
     */
    private $name;

    /**
     * @var string|null The dummy name alias
     *
     * @ORM\Column(nullable=true)
     *
     * @ApiProperty(iri="https://schema.org/alternateName")
     */
    private $alias;

    /**
     * @var array foo
     */
    private $foo;

    /**
     * @var string|null A short description of the item
     *
     * @ORM\Column(nullable=true)
     *
     * @ApiProperty(iri="https://schema.org/description")
     */
    public $description;

    /**
     * @var string|null A dummy
     *
     * @ORM\Column(nullable=true)
     */
    public $dummy;

    /**
     * @var bool|null A dummy boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    public $dummyBoolean;

    /**
     * @var \DateTime|null A dummy date
     *
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @ApiProperty(iri="http://schema.org/DateTime")
     */
    public $dummyDate;

    /**
     * @var float|null A dummy float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    public $dummyFloat;

    /**
     * @var string|null A dummy price
     *
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    public $dummyPrice;

    /**
     * @var RelatedDummy|null A related dummy
     *
     * @ORM\ManyToOne(targetEntity="RelatedDummy")
     *
     * @ApiProperty(push=true)
     */
    public $relatedDummy;

    /**
     * @var Collection<int, RelatedDummy> Several dummies
     *
     * @ORM\ManyToMany(targetEntity="RelatedDummy")
     *
     * @ApiSubresource
     */
    public $relatedDummies;

    /**
     * @var array|null serialize data
     *
     * @ORM\Column(type="json", nullable=true)
     */
    public $jsonData;

    /**
     * @var array<string>|null
     *
     * @ORM\Column(type="simple_array", nullable=true)
     */
    public $arrayData;

    /**
     * @var string|null
     *
     * @ORM\Column(nullable=true)
     */
    public $nameConverted;

    /**
     * @var RelatedOwnedDummy|null
     *
     * @ORM\OneToOne(targetEntity="RelatedOwnedDummy", cascade={"persist"}, mappedBy="owningDummy")
     */
    public $relatedOwnedDummy;

    /**
     * @var RelatedOwningDummy|null
     *
     * @ORM\OneToOne(targetEntity="RelatedOwningDummy", cascade={"persist"}, inversedBy="ownedDummy")
     */
    public $relatedOwningDummy;

    public static function staticMethod()
    {
    }

    public function __construct()
    {
        $this->relatedDummies = new ArrayCollection();
        $this->jsonData = [];
        $this->arrayData = [];
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function fooBar($baz)
    {
    }

    public function getFoo()
    {
        return $this->foo;
    }

    public function setFoo(array $foo = null)
    {
        $this->foo = $foo;
    }

    public function setDummyDate(\DateTime $dummyDate = null)
    {
        $this->dummyDate = $dummyDate;
    }

    public function getDummyDate()
    {
        return $this->dummyDate;
    }

    public function setDummyPrice($dummyPrice)
    {
        $this->dummyPrice = $dummyPrice;

        return $this;
    }

    public function getDummyPrice()
    {
        return $this->dummyPrice;
    }

    public function setJsonData($jsonData)
    {
        $this->jsonData = $jsonData;
    }

    public function getJsonData()
    {
        return $this->jsonData;
    }

    public function setArrayData($arrayData)
    {
        $this->arrayData = $arrayData;
    }

    public function getArrayData()
    {
        return $this->arrayData;
    }

    public function getRelatedDummy()
    {
        return $this->relatedDummy;
    }

    public function setRelatedDummy(RelatedDummy $relatedDummy)
    {
        $this->relatedDummy = $relatedDummy;
    }

    public function addRelatedDummy(RelatedDummy $relatedDummy)
    {
        $this->relatedDummies->add($relatedDummy);
    }

    public function getRelatedOwnedDummy()
    {
        return $this->relatedOwnedDummy;
    }

    public function setRelatedOwnedDummy(RelatedOwnedDummy $relatedOwnedDummy)
    {
        $this->relatedOwnedDummy = $relatedOwnedDummy;

        if ($this !== $this->relatedOwnedDummy->getOwningDummy()) {
            $this->relatedOwnedDummy->setOwningDummy($this);
        }
    }

    public function getRelatedOwningDummy()
    {
        return $this->relatedOwningDummy;
    }

    public function setRelatedOwningDummy(RelatedOwningDummy $relatedOwningDummy)
    {
        $this->relatedOwningDummy = $relatedOwningDummy;
    }

    public function isDummyBoolean(): ?bool
    {
        return $this->dummyBoolean;
    }

    /**
     * @param bool $dummyBoolean
     */
    public function setDummyBoolean($dummyBoolean)
    {
        $this->dummyBoolean = $dummyBoolean;
    }

    public function setDummy($dummy = null)
    {
        $this->dummy = $dummy;
    }

    public function getDummy()
    {
        return $this->dummy;
    }

    public function getRelatedDummies()
    {
        return $this->relatedDummies;
    }
}
