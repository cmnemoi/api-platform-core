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

namespace ApiPlatform\Core\Tests\Metadata\Extractor;

use ApiPlatform\Core\Metadata\Extractor\XmlExtractor;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo Fidry <theo.fidry@gmail.com>
 */
class XmlExtractorTest extends ExtractorTestCase
{
    protected function getExtractorClass(): string
    {
        return XmlExtractor::class;
    }

    protected function getEmptyResourcesFile(): string
    {
        return __DIR__.'/../../../Fixtures/FileConfigurations/resources_empty.xml';
    }

    protected function getEmptyOperationFile(): string
    {
        return __DIR__.'/../../../Fixtures/FileConfigurations/empty-operation.xml';
    }

    protected function getCorrectResourceFile(): string
    {
        return __DIR__.'/../../../Fixtures/FileConfigurations/resources.xml';
    }

    protected function getResourceWithParametersFile(): string
    {
        return __DIR__.'/../../../Fixtures/FileConfigurations/resources_with_parameters.xml';
    }
}
