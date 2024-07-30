<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Normalizer;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use SkriptManufaktur\SimpleRestBundle\Component\EntityIdDenormalizer;
use SkriptManufaktur\SimpleRestBundle\Component\EntityUuidDenormalizer;
use SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\EmbeddedDummyEntity;
use SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\EmbeddedUuidDummyEntity;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Normalizer\UnwrappingDenormalizer;
use Symfony\Component\Serializer\Serializer;

trait SerializerMockingTrait
{
    private function createIdSerializer(string $hydrationMethod): Serializer
    {
        $registry = $this->createManagerRegistry($hydrationMethod, EmbeddedDummyEntity::class, function (int $id) {
            $matching = array_filter($this->embeddeds, fn (EmbeddedDummyEntity $e) => $e->getId() === $id);

            if (empty($matching)) {
                return null;
            }

            return reset($matching);
        });

        return new Serializer([
            new UnwrappingDenormalizer(),
            new EntityIdDenormalizer($registry),
            new UidNormalizer(),
            new DateTimeNormalizer(),
            new ArrayDenormalizer(),
            new ObjectNormalizer(propertyTypeExtractor: new ReflectionExtractor()),
        ]);
    }

    private function createUuidSerializer(string $hydrationMethod): Serializer
    {
        $registry = $this->createManagerRegistry($hydrationMethod, EmbeddedUuidDummyEntity::class, function (string $uuid) {
            $matching = array_filter($this->embeddeds, fn (EmbeddedUuidDummyEntity $e) => $e->getId() === $uuid);

            if (empty($matching)) {
                return null;
            }

            return reset($matching);
        });

        return new Serializer([
            new UnwrappingDenormalizer(),
            new EntityUuidDenormalizer($registry),
            new UidNormalizer(),
            new DateTimeNormalizer(),
            new ArrayDenormalizer(),
            new ObjectNormalizer(propertyTypeExtractor: new ReflectionExtractor()),
        ]);
    }

    private function createManagerRegistry(string $hydratingMethod, string $className, callable $repoCallback): ManagerRegistry
    {
        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->onlyMethods(['find', 'findAll'])
            ->addMethods(['getOne'])
            ->getMock()
        ;

        $repository
            ->method($hydratingMethod)
            ->willReturnCallback($repoCallback)
        ;

        $objManager = $this->createMock(ObjectManager::class);
        $objManager
            ->method('getRepository')
            ->with($className)
            ->willReturn($repository)
        ;

        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->method('getManagerForClass')
            ->with($className)
            ->willReturn($objManager)
        ;

        return $registry;
    }
}
