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
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource(
 *     itemOperations={
 *         "get",
 *         "patch"={"input_formats"={"json"={"application/merge-patch+json"}, "jsonapi"}}
 *     }
 * )
 *
 * @ORM\Entity
 */
class PatchDummy
{
    /**
     * @ORM\Id
     *
     * @ORM\Column(type="integer")
     *
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(nullable=true)
     */
    public $name;
}
