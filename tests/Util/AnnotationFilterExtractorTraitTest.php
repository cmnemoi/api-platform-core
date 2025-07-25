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

namespace ApiPlatform\Tests\Util;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Serializer\Filter\GroupFilter;
use ApiPlatform\Serializer\Filter\PropertyFilter;
use ApiPlatform\Tests\Fixtures\DummyEntityFilterAnnotated;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Util\AnnotationFilterExtractor;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AnnotationFilterExtractorTraitTest extends KernelTestCase
{
    private $extractor;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->extractor = new AnnotationFilterExtractor(new AnnotationReader());
    }

    public function testReadAnnotations()
    {
        $reflectionClass = new \ReflectionClass(DummyCar::class);
        $this->assertEquals($this->extractor->getFilters($reflectionClass), [
            'annotated_api_platform_tests_fixtures_test_bundle_entity_dummy_car_api_platform_core_bridge_doctrine_orm_filter_date_filter' => [
                ['properties' => ['id' => 'exclude_null', 'colors' => 'exclude_null', 'name' => 'exclude_null', 'canSell' => 'exclude_null', 'availableAt' => 'exclude_null', 'brand' => 'exclude_null', 'secondColors' => 'exclude_null', 'thirdColors' => 'exclude_null', 'uuid' => 'exclude_null', 'info' => 'exclude_null']],
                DateFilter::class,
            ],
            'annotated_api_platform_tests_fixtures_test_bundle_entity_dummy_car_api_platform_core_bridge_doctrine_orm_filter_boolean_filter' => [
                [],
                BooleanFilter::class,
            ],
            'annotated_api_platform_tests_fixtures_test_bundle_entity_dummy_car_api_platform_core_bridge_doctrine_orm_filter_search_filter' => [
                ['properties' => ['name' => 'partial', 'colors.prop' => 'ipartial', 'colors' => 'exact', 'secondColors' => 'exact', 'thirdColors' => 'exact', 'uuid' => 'exact']],
                SearchFilter::class,
            ],
            'annotated_api_platform_tests_fixtures_test_bundle_entity_dummy_car_api_platform_serializer_filter_property_filter' => [
                ['parameterName' => 'foobar'],
                PropertyFilter::class,
            ],
            'annotated_api_platform_tests_fixtures_test_bundle_entity_dummy_car_api_platform_serializer_filter_group_filter' => [
                ['parameterName' => 'foobargroups'],
                GroupFilter::class,
            ],
            'annotated_api_platform_tests_fixtures_test_bundle_entity_dummy_car_api_platform_serializer_filter_group_filter_override' => [
                ['parameterName' => 'foobargroups_override'],
                GroupFilter::class,
            ],
        ]);
    }

    public function testReadOrderAnnotations()
    {
        $reflectionClass = new \ReflectionClass(DummyEntityFilterAnnotated::class);

        $this->assertEquals($this->extractor->getFilters($reflectionClass), [
            'annotated_api_platform_tests_fixtures_dummy_entity_filter_annotated_api_platform_core_bridge_doctrine_orm_filter_order_filter' => [
                [
                    'orderParameterName' => 'positionOrder',
                    'properties' => [
                        'position' => null, 'priority' => null, 'number' => 'ASC',
                    ],
                ],
                OrderFilter::class,
            ],
        ]);
    }
}
