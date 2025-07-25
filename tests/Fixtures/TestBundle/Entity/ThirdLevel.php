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
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Third Level.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource
 *
 * @ORM\Entity
 */
class ThirdLevel
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
     * @var int
     *
     * @ORM\Column(type="integer")
     *
     * @Groups({"barcelona", "chicago"})
     */
    private $level = 3;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $test = true;

    /**
     * @ApiSubresource
     *
     * @ORM\ManyToOne(targetEntity="FourthLevel", cascade={"persist"})
     *
     * @Groups({"barcelona", "chicago", "friends"})
     */
    public $fourthLevel;

    /**
     * @ORM\ManyToOne(targetEntity=FourthLevel::class, cascade={"persist"})
     */
    public $badFourthLevel;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    /**
     * @param int $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
    }

    public function isTest(): bool
    {
        return $this->test;
    }

    /**
     * @param bool $test
     */
    public function setTest($test)
    {
        $this->test = $test;
    }

    public function getFourthLevel(): ?FourthLevel
    {
        return $this->fourthLevel;
    }

    public function setFourthLevel(FourthLevel $fourthLevel = null)
    {
        $this->fourthLevel = $fourthLevel;
    }
}
