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

use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ApiResource(urlGenerationStrategy=UrlGeneratorInterface::NET_PATH)
 *
 * @ODM\Document
 */
class NetworkPathRelationDummy
{
    /**
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;

    /**
     * @ODM\ReferenceMany(targetDocument=NetworkPathDummy::class, mappedBy="networkPathRelationDummy")
     *
     * @ApiSubresource
     */
    public $networkPathDummies;

    public function __construct()
    {
        $this->networkPathDummies = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }
}
