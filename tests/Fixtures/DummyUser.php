<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Fixtures;

use InvalidArgumentException;
use Symfony\Component\Security\Core\User\UserInterface;

class DummyUser implements UserInterface
{
    private string $username;
    private ?string $password;
    private array $roles;


    public function __construct(?string $username, array $roles = [])
    {
        if ('' === $username || null === $username) {
            throw new InvalidArgumentException('The username cannot be empty.');
        }

        $this->username = $username;
        $this->roles = $roles;
    }

    public function __toString(): string
    {
        return $this->getUserIdentifier();
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function eraseCredentials(): void
    {
    }
}
