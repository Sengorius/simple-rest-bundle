<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Fixtures;

use Symfony\Component\Validator\Constraints as Assert;

class EmbeddedDummyEntity
{
    #[Assert\GreaterThanOrEqual(value: 0)]
    private int $id = 0;

    #[Assert\NotBlank]
    private string $type = '';

    private bool $active = true;
    private DummyEntity|null $dummy = null;


    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): EmbeddedDummyEntity
    {
        $this->id = $id;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): EmbeddedDummyEntity
    {
        $this->type = $type;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): EmbeddedDummyEntity
    {
        $this->active = $active;

        return $this;
    }

    public function getDummy(): DummyEntity|null
    {
        return $this->dummy;
    }

    public function setDummy(DummyEntity|null $dummy): EmbeddedDummyEntity
    {
        $this->dummy = $dummy;

        return $this;
    }
}
