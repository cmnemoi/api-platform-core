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
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\PasswordResetRequest;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\PasswordResetRequestResult;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\RecoverPasswordInput;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\RecoverPasswordOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\Security\AbstractSecurityUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * A User.
 *
 * @ORM\Entity
 *
 * @ORM\Table(name="user_test")
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
     * @ORM\Id
     *
     * @ORM\Column(type="integer")
     *
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string|null
     *
     * @Groups({"user"})
     */
    private $email;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Groups({"user"})
     */
    private $fullname;

    /**
     * @var string|null
     *
     * @Groups({"user-write"})
     */
    private $plainPassword;

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

    public function eraseCredentials(): void
    {
    }
}
