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

namespace ApiPlatform\Tests\Fixtures\TestBundle\MessageHandler;

use ApiPlatform\Tests\Fixtures\TestBundle\Dto\PasswordResetRequest;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\PasswordResetRequestResult;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

if (\PHP_VERSION_ID >= 80000 && class_exists(AsMessageHandler::class)) {
    #[AsMessageHandler]
    class PasswordResetRequestHandler
    {
        public function __invoke(PasswordResetRequest $passwordResetRequest): PasswordResetRequestResult
        {
            if ('does-not-exist@example.com' === $passwordResetRequest->getEmail()) {
                throw new NotFoundHttpException('User does not exist.');
            }

            return new PasswordResetRequestResult(new \DateTimeImmutable('2019-07-05T15:44:00Z'));
        }
    }
} else {
    class PasswordResetRequestHandler implements MessageHandlerInterface
    {
        public function __invoke(PasswordResetRequest $passwordResetRequest): PasswordResetRequestResult
        {
            if ('does-not-exist@example.com' === $passwordResetRequest->getEmail()) {
                throw new NotFoundHttpException('User does not exist.');
            }

            return new PasswordResetRequestResult(new \DateTimeImmutable('2019-07-05T15:44:00Z'));
        }
    }
}
