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

/**
 * Custom Identifier Dummy.
 *
 * @ApiResource
 *
 * @ODM\Document
 */
class CustomIdentifierDummy
{
    /**
     * @var int The custom identifier
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $customId;

    /**
     * @var string The dummy name
     *
     * @ODM\Field
     */
    private $name;

    public function getCustomId(): int
    {
        return $this->customId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
