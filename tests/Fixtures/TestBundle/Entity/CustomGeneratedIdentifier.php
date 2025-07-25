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
 * Custom identifier.
 *
 * @ApiResource
 *
 * @ORM\Entity
 */
class CustomGeneratedIdentifier
{
    /**
     * @ORM\Id
     *
     * @ORM\Column(type="string")
     *
     * @ORM\GeneratedValue(strategy="CUSTOM")
     *
     * @ORM\CustomIdGenerator(class="ApiPlatform\Tests\Fixtures\TestBundle\Doctrine\Generator\UuidGenerator")
     */
    private $id;

    public function getId()
    {
        return $this->id;
    }
}
