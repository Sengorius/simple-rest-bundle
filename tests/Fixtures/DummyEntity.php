<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Fixtures;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class DummyEntity
{
    private int $id = 0;
    private string $username = '';
    private string $email = '';
    private Collection $embeds;
    private Collection $uuidEmbeds;
    private DateTime $created;


    public function __construct()
    {
        $this->embeds = new ArrayCollection();
        $this->uuidEmbeds = new ArrayCollection();
        $this->created = new DateTime('now');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): DummyEntity
    {
        $this->id = $id;

        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): DummyEntity
    {
        $this->username = $username;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): DummyEntity
    {
        $this->email = $email;

        return $this;
    }

    public function getEmbeds(): Collection
    {
        return $this->embeds;
    }

    public function addEmbed(EmbeddedDummyEntity $embed): DummyEntity
    {
        if (!$this->embeds->contains($embed)) {
            $embed->setDummy($this);
            $this->embeds->add($embed);
        }

        return $this;
    }

    public function removeEmbed(EmbeddedDummyEntity $embed): DummyEntity
    {
        if ($this->embeds->contains($embed)) {
            $embed->setDummy(null);
            $this->embeds->removeElement($embed);
        }

        return $this;
    }

    public function getUuidEmbeds(): Collection
    {
        return $this->uuidEmbeds;
    }

    public function addUuidEmbed(EmbeddedUuidDummyEntity $embed): DummyEntity
    {
        if (!$this->uuidEmbeds->contains($embed)) {
            $embed->setDummy($this);
            $this->uuidEmbeds->add($embed);
        }

        return $this;
    }

    public function removeUuidEmbed(EmbeddedUuidDummyEntity $embed): DummyEntity
    {
        if ($this->uuidEmbeds->contains($embed)) {
            $embed->setDummy(null);
            $this->uuidEmbeds->removeElement($embed);
        }

        return $this;
    }

    public function getCreated(): DateTime
    {
        return $this->created;
    }
}
