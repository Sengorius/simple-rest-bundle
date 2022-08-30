<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Fixtures;

class EmbeddedUuidDummyEntity
{
    private string $id = '';
    private string $type = '';
    private bool $active = true;
    private ?DummyEntity $dummy = null;


    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): EmbeddedUuidDummyEntity
    {
        $this->id = $id;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): EmbeddedUuidDummyEntity
    {
        $this->type = $type;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): EmbeddedUuidDummyEntity
    {
        $this->active = $active;

        return $this;
    }

    public function getDummy(): ?DummyEntity
    {
        return $this->dummy;
    }

    public function setDummy(?DummyEntity $dummy): EmbeddedUuidDummyEntity
    {
        $this->dummy = $dummy;

        return $this;
    }
}
