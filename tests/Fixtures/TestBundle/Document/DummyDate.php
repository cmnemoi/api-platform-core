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

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Doctrine\Odm\Filter\DateFilter;
use ApiPlatform\Doctrine\Odm\Filter\SearchFilter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Dummy Date.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 *
 * @ApiResource(attributes={
 *     "filters"={"my_dummy_date.mongodb.date"}
 * })
 *
 * @ApiFilter(SearchFilter::class, properties={"dummyDate"})
 * @ApiFilter(DateFilter::class, properties={
 *     "dateIncludeNullAfter"=DateFilter::INCLUDE_NULL_AFTER,
 *     "dateIncludeNullBefore"=DateFilter::INCLUDE_NULL_BEFORE,
 *     "dateIncludeNullBeforeAndAfter"=DateFilter::INCLUDE_NULL_BEFORE_AND_AFTER
 * })
 *
 * @ODM\Document
 */
class DummyDate
{
    /**
     * @var int|null The id
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;

    /**
     * @var \DateTime|null The dummy date
     *
     * @ODM\Field(type="date")
     */
    public $dummyDate;

    /**
     * @var \DateTime|null
     *
     * @ODM\Field(type="date")
     */
    public $dateIncludeNullAfter;

    /**
     * @var \DateTime|null
     *
     * @ODM\Field(type="date")
     */
    public $dateIncludeNullBefore;

    /**
     * @var \DateTime|null
     *
     * @ODM\Field(type="date")
     */
    public $dateIncludeNullBeforeAndAfter;

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }
}
