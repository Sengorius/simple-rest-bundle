<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Fixtures;

class EmbeddedDummyEntity
{
    private int $id = 0;
    private string $type = '';
    private bool $active = true;
    private ?DummyEntity $dummy = null;


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

    public function getDummy(): ?DummyEntity
    {
        return $this->dummy;
    }

    public function setDummy(?DummyEntity $dummy): EmbeddedDummyEntity
    {
        $this->dummy = $dummy;

        return $this;
    }
}
