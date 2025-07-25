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
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\PasswordResetRequest;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\PasswordResetRequestResult;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\RecoverPasswordInput;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\RecoverPasswordOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\Security\AbstractSecurityUser;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * A User.
 *
 * @ODM\Document(collection="user_test")
 *
 * @ApiResource(
 *     attributes={
 *         "normalization_context"={"groups"={"user", "user-read"}},
 *         "denormalization_context"={"groups"={"user", "user-write"}}
 *     },
 *     collectionOperations={
 *         "post",
 *         "get",
 *         "post_password_reset_request"={
 *             "method"="POST",
 *             "path"="/users/password_reset_request",
 *             "messenger"="input",
 *             "input"=PasswordResetRequest::class,
 *             "output"=PasswordResetRequestResult::class,
 *             "normalization_context"={
 *                 "groups"={"user_password_reset_request"},
 *             },
 *             "denormalization_context"={
 *                 "groups"={"user_password_reset_request"},
 *             },
 *         },
 *     },
 *     itemOperations={"get", "put", "delete",
 *         "recover_password"={
 *             "input"=RecoverPasswordInput::class, "output"=RecoverPasswordOutput::class, "method"="PUT", "path"="users/recover/{id}"
 *         }
 *     }
 * )
 *
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class User extends AbstractSecurityUser
{
    /**
     * @var int|null
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    protected $id;

    /**
     * @var string|null
     *
     * @Groups({"user"})
     */
    protected $email;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", nullable=true)
     *
     * @Groups({"user"})
     */
    protected $fullname;

    /**
     * @var string|null
     *
     * @Groups({"user-write"})
     */
    protected $plainPassword;

    /**
     * @var string|null
     *
     * @Groups({"user"})
     */
    protected $username;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }

    public function setFullname(?string $fullname): self
    {
        $this->fullname = $fullname;

        return $this;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function getUsername(): string
    {
        return (string) $this->email;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getPassword(): ?string
    {
        return null;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials()
    {
    }
}
