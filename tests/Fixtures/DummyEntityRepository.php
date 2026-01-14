<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Fixtures;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DummyEntityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmbeddedDummyEntity::class);
    }

    public function getOneFromId(int $id): EmbeddedDummyEntity
    {
        return new EmbeddedDummyEntity()->setId($id);
    }

    public function getOneFromUuid(string $uuid): EmbeddedUuidDummyEntity
    {
        return new EmbeddedUuidDummyEntity()->setId($uuid);
    }
}
