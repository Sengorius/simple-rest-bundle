<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Fixtures;

use InvalidArgumentException;
use Symfony\Component\Security\Core\User\UserInterface;

class DummyUser implements UserInterface
{
    private string|null $password = null;


    public function __construct(private readonly string|null $username, private readonly array $roles = [])
    {
        if ('' === $username || null === $username) {
            throw new InvalidArgumentException('The username cannot be empty.');
        }
    }

    public function __toString(): string
    {
        return $this->getUserIdentifier();
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPassword(): string|null
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getSalt(): string|null
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
