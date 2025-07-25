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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Metadata;

use ApiPlatform\Metadata\HttpOperation;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class Get extends HttpOperation
{
    public function __construct(...$args)
    {
        parent::__construct(self::METHOD_GET, ...$args);
    }
}
