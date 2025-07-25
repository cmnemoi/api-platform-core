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

namespace ApiPlatform\Tests\Doctrine\Orm\Filter;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Test\DoctrineOrmFilterTestCase;
use ApiPlatform\Tests\Doctrine\Common\Filter\DateFilterTestTrait;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDate;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyImmutableDate;

/**
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Vincent CHALAMON <vincentchalamon@gmail.com>
 */
class DateFilterTest extends DoctrineOrmFilterTestCase
{
    use DateFilterTestTrait;

    protected $filterClass = DateFilter::class;

    public function testApplyDate(): void
    {
        $filters = ['dummyDate' => ['after' => '2015-04-05']];

        $queryBuilder = $this->repository->createQueryBuilder('o');

        $filter = new DateFilter($this->managerRegistry, null, ['dummyDate' => null]);
        $filter->apply($queryBuilder, new QueryNameGenerator(), DummyDate::class, null, ['filters' => $filters]);

        $this->assertEquals(new \DateTime('2015-04-05'), $queryBuilder->getParameters()[0]->getValue());
        $this->assertInstanceOf(\DateTime::class, $queryBuilder->getParameters()[0]->getValue());
    }

    public function testApplyDateImmutable(): void
    {
        $filters = ['dummyDate' => ['after' => '2015-04-05']];

        $queryBuilder = $this->repository->createQueryBuilder('o');

        $filter = new DateFilter($this->managerRegistry, null, ['dummyDate' => null]);
        $filter->apply($queryBuilder, new QueryNameGenerator(), DummyImmutableDate::class, null, ['filters' => $filters]);

        $this->assertEquals(new \DateTimeImmutable('2015-04-05'), $queryBuilder->getParameters()[0]->getValue());
        $this->assertInstanceOf(\DateTimeImmutable::class, $queryBuilder->getParameters()[0]->getValue());
    }

    public function provideApplyTestData(): array
    {
        return array_merge_recursive(
            $this->provideApplyTestArguments(),
            [
                'after (all properties enabled)' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyDate >= :dummyDate_p1', Dummy::class),
                ],
                'after but not equals (all properties enabled)' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyDate > :dummyDate_p1', Dummy::class),
                ],
                'after' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyDate >= :dummyDate_p1', Dummy::class),
                ],
                'after but not equals' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyDate > :dummyDate_p1', Dummy::class),
                ],
                'before (all properties enabled)' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyDate <= :dummyDate_p1', Dummy::class),
                ],
                'before but not equals (all properties enabled)' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyDate < :dummyDate_p1', Dummy::class),
                ],
                'before' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyDate <= :dummyDate_p1', Dummy::class),
                ],
                'before but not equals' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyDate < :dummyDate_p1', Dummy::class),
                ],
                'before + after (all properties enabled)' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyDate <= :dummyDate_p1 AND o.dummyDate >= :dummyDate_p2', Dummy::class),
                ],
                'before but not equals + after but not equals (all properties enabled)' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyDate < :dummyDate_p1 AND o.dummyDate > :dummyDate_p2', Dummy::class),
                ],
                'before + after' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyDate <= :dummyDate_p1 AND o.dummyDate >= :dummyDate_p2', Dummy::class),
                ],
                'before but not equals + after but not equals' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyDate < :dummyDate_p1 AND o.dummyDate > :dummyDate_p2', Dummy::class),
                ],
                'property not enabled' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                ],
                'nested property' => [
                    sprintf('SELECT o FROM %s o INNER JOIN o.relatedDummy relatedDummy_a1 WHERE relatedDummy_a1.dummyDate >= :dummyDate_p1', Dummy::class),
                ],
                'after (exclude_null)' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyDate IS NOT NULL AND o.dummyDate >= :dummyDate_p1', Dummy::class),
                ],
                'after (include_null_after)' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyDate >= :dummyDate_p1 OR o.dummyDate IS NULL', Dummy::class),
                ],
                'include null before and after (include_null_before_and_after)' => [
                    sprintf('SELECT o FROM %s o WHERE o.dummyDate >= :dummyDate_p1 OR o.dummyDate IS NULL', Dummy::class),
                ],
                'bad date format' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                ],
            ]
        );
    }
}
