<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Fixtures;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

class DummyEntity
{
    #[Assert\GreaterThanOrEqual(value: 0)]
    private int $id = 0;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3)]
    private string $username = '';

    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email = '';

    #[Assert\All([
        new Assert\Type(type: EmbeddedDummyEntity::class),
        new Assert\Valid(),
    ], groups: ['Default', 'with-embeds'])]
    private Collection $embeds;

    #[Assert\All([
        new Assert\Type(type: EmbeddedUuidDummyEntity::class),
        new Assert\Valid(),
    ], groups: ['Default', 'with-embeds'])]
    private Collection $uuidEmbeds;

    #[Assert\Type(type: DateTime::class)]
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
