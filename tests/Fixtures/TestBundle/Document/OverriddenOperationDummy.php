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
 * Overridden Operation Dummy.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 *
 * @ApiResource(
 *     attributes={
 *         "normalization_context"={"groups"={"overridden_operation_dummy_read"}},
 *         "denormalization_context"={"groups"={"overridden_operation_dummy_write"}}
 *     },
 *     collectionOperations={
 *         "get"={"method"="GET"},
 *         "post"={"method"="POST"},
 *         "swagger"={
 *             "path"="/override/swagger",
 *             "method"="GET",
 *         }
 *     },
 *     itemOperations={
 *         "swagger"={
 *             "method"="GET",
 *         },
 *         "get"={
 *             "method"="GET",
 *             "normalization_context"={"groups"={"overridden_operation_dummy_get"}},
 *             "denormalization_context"={"groups"={"overridden_operation_dummy_get"}}
 *         },
 *         "put"={
 *             "method"="PUT",
 *             "normalization_context"={"groups"={"overridden_operation_dummy_put"}},
 *             "denormalization_context"={"groups"={"overridden_operation_dummy_put"}}
 *         },
 *         "delete"={"method"="DELETE"}
 *     }
 * )
 *
 * @ODM\Document
 */
class OverriddenOperationDummy
{
    /**
     * @var int The id
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;

    /**
     * @var string The dummy name
     *
     * @ODM\Field
     *
     * @Assert\NotBlank
     *
     * @Groups({"overridden_operation_dummy_read", "overridden_operation_dummy_write", "overridden_operation_dummy_get"})
     *
     * @ApiProperty(iri="http://schema.org/name")
     */
    private $name;

    /**
     * @var string The dummy name alias
     *
     * @ODM\Field(nullable=true)
     *
     * @Groups({"overridden_operation_dummy_read", "overridden_operation_dummy_put", "overridden_operation_dummy_get"})
     *
     * @ApiProperty(iri="https://schema.org/alternateName")
     */
    private $alias;

    /**
     * @var string A short description of the item
     *
     * @ODM\Field(nullable=true)
     *
     * @Groups({"overridden_operation_dummy_read", "overridden_operation_dummy_write", "overridden_operation_dummy_get", "overridden_operation_dummy_put"})
     *
     * @ApiProperty(iri="https://schema.org/description")
     */
    public $description;

    /**
     * @ODM\Field(nullable=true)
     *
     * @Groups({"overridden_operation_dummy_write"})
     */
    public $notGettable;

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
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
}
