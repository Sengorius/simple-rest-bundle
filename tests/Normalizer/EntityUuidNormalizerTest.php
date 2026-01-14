<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Normalizer;

use DateTime;
use PHPUnit\Framework\TestCase;
use SkriptManufaktur\SimpleRestBundle\Component\EntityUuidDenormalizer;
use SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DummyEntity;
use SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\EmbeddedUuidDummyEntity;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

class EntityUuidNormalizerTest extends TestCase
{
    use SerializerMockingTrait;

    private array $embeddeds;


    protected function setUp(): void
    {
        parent::setUp();

        $this->embeddeds = [
            new EmbeddedUuidDummyEntity()
                ->setId('56c190d2-c864-43c5-8c2a-61092b2474c6')
                ->setType('t1')
                ->setActive(true)
            ,
            new EmbeddedUuidDummyEntity()
                ->setId('a92fb3c0-5132-4ed7-96e3-b917dbabf681')
                ->setType('t2')
                ->setActive(true)
            ,
            new EmbeddedUuidDummyEntity()
                ->setId('6e6e7d62-f2f3-4414-9da9-01e1768da6ea')
                ->setType('t3')
                ->setActive(true)
            ,
            new EmbeddedUuidDummyEntity()
                ->setId('f079b6da-3f5c-4655-850c-abd7ab22d65d')
                ->setType('t1')
                ->setActive(false)
            ,
        ];
    }

    public function testMock(): void
    {
        $registry = $this->createManagerRegistry(
            'find',
            EmbeddedUuidDummyEntity::class,
            function (string $id) {
                $matching = array_filter($this->embeddeds, fn (EmbeddedUuidDummyEntity $e) => $e->getId() === $id);

                if (empty($matching)) {
                    return null;
                }

                return reset($matching);
            }
        );

        $manager = $registry->getManagerForClass(EmbeddedUuidDummyEntity::class);
        $repository = $manager->getRepository(EmbeddedUuidDummyEntity::class);
        $entity = $repository->find('6e6e7d62-f2f3-4414-9da9-01e1768da6ea');

        static::assertInstanceOf(EmbeddedUuidDummyEntity::class, $entity);
        static::assertSame('6e6e7d62-f2f3-4414-9da9-01e1768da6ea', $entity->getId());
        static::assertSame('t3', $entity->getType());
        static::assertTrue($entity->isActive());
    }

    /** @throws ExceptionInterface */
    public function testWithGivenClassMap(): void
    {
        $data = [
            'id' => 1,
            'username' => 'Tom',
            'email' => 'tom@example.com',
            'uuidEmbeds' => ['a92fb3c0-5132-4ed7-96e3-b917dbabf681', 'f079b6da-3f5c-4655-850c-abd7ab22d65d'],
            'created' => '2022-08-20 14:20:54',
        ];

        $result = $this->createUuidSerializer('find')->denormalize($data, DummyEntity::class, null, [
            EntityUuidDenormalizer::CLASS_MAP => [EmbeddedUuidDummyEntity::class => 'find'],
        ]);

        static::assertInstanceOf(DummyEntity::class, $result);
        static::assertInstanceOf(DateTime::class, $result->getCreated());
        static::assertSame(1, $result->getId());
        static::assertSame('Tom', $result->getUsername());
        static::assertSame('tom@example.com', $result->getEmail());
        static::assertSame(2, $result->getUuidEmbeds()->count());

        $firstEmbed = $result->getUuidEmbeds()->first();
        static::assertInstanceOf(EmbeddedUuidDummyEntity::class, $firstEmbed);
        static::assertSame('a92fb3c0-5132-4ed7-96e3-b917dbabf681', $firstEmbed->getId());
        static::assertSame('t2', $firstEmbed->getType());
        static::assertTrue($firstEmbed->isActive());
    }

    /** @throws ExceptionInterface */
    public function testWithEmptyCollection(): void
    {
        $data = [
            'id' => 1,
            'username' => 'Tom',
            'email' => 'tom@example.com',
            'embeds' => [],
            'created' => '2022-08-20 14:20:54',
        ];

        $result = $this->createUuidSerializer('find')->denormalize($data, DummyEntity::class, null, [
            EntityUuidDenormalizer::CLASS_MAP => [EmbeddedUuidDummyEntity::class => 'find'],
        ]);

        static::assertInstanceOf(DummyEntity::class, $result);
        static::assertInstanceOf(DateTime::class, $result->getCreated());
        static::assertSame(1, $result->getId());
        static::assertSame('Tom', $result->getUsername());
        static::assertSame('tom@example.com', $result->getEmail());
        static::assertTrue($result->getUuidEmbeds()->isEmpty());
    }

    /** @throws ExceptionInterface */
    public function testWithSimpleClassMap(): void
    {
        $data = [
            'id' => 1,
            'username' => 'Tom',
            'email' => 'tom@example.com',
            'uuidEmbeds' => ['a92fb3c0-5132-4ed7-96e3-b917dbabf681', 'f079b6da-3f5c-4655-850c-abd7ab22d65d'],
            'created' => '2022-08-20 14:20:54',
        ];

        $result = $this->createUuidSerializer('find')->denormalize($data, DummyEntity::class, null, [
            EntityUuidDenormalizer::CLASS_MAP => [EmbeddedUuidDummyEntity::class],
        ]);

        static::assertInstanceOf(DummyEntity::class, $result);
        static::assertInstanceOf(DateTime::class, $result->getCreated());
        static::assertSame(1, $result->getId());
        static::assertSame('Tom', $result->getUsername());
        static::assertSame('tom@example.com', $result->getEmail());
        static::assertSame(2, $result->getUuidEmbeds()->count());

        $firstEmbed = $result->getUuidEmbeds()->first();
        static::assertInstanceOf(EmbeddedUuidDummyEntity::class, $firstEmbed);
        static::assertSame('a92fb3c0-5132-4ed7-96e3-b917dbabf681', $firstEmbed->getId());
        static::assertSame('t2', $firstEmbed->getType());
        static::assertTrue($firstEmbed->isActive());
    }

    /** @throws ExceptionInterface */
    public function testWithDifferentClassMapMethod(): void
    {
        $data = [
            'id' => 1,
            'username' => 'Tom',
            'email' => 'tom@example.com',
            'uuidEmbeds' => ['a92fb3c0-5132-4ed7-96e3-b917dbabf681', 'f079b6da-3f5c-4655-850c-abd7ab22d65d'],
            'created' => '2022-08-20 14:20:54',
        ];

        $result = $this->createUuidSerializer('getOneFromUuid')->denormalize($data, DummyEntity::class, null, [
            EntityUuidDenormalizer::CLASS_MAP => [EmbeddedUuidDummyEntity::class => 'getOneFromUuid'],
        ]);

        static::assertInstanceOf(DummyEntity::class, $result);
        static::assertInstanceOf(DateTime::class, $result->getCreated());
        static::assertSame(1, $result->getId());
        static::assertSame('Tom', $result->getUsername());
        static::assertSame('tom@example.com', $result->getEmail());
        static::assertSame(2, $result->getUuidEmbeds()->count());

        $firstEmbed = $result->getUuidEmbeds()->first();
        static::assertInstanceOf(EmbeddedUuidDummyEntity::class, $firstEmbed);
        static::assertSame('a92fb3c0-5132-4ed7-96e3-b917dbabf681', $firstEmbed->getId());
        static::assertSame('t2', $firstEmbed->getType());
        static::assertTrue($firstEmbed->isActive());
    }

    /** @throws ExceptionInterface */
    public function testWithEmptyClassMap(): void
    {
        $data = [
            'id' => 1,
            'username' => 'Tom',
            'email' => 'tom@example.com',
            'uuidEmbeds' => ['a92fb3c0-5132-4ed7-96e3-b917dbabf681', 'f079b6da-3f5c-4655-850c-abd7ab22d65d'],
            'created' => '2022-08-20 14:20:54',
        ];

        $result = $this->createUuidSerializer('find')->denormalize($data, DummyEntity::class, null, [
            EntityUuidDenormalizer::CLASS_MAP => [],
        ]);

        static::assertInstanceOf(DummyEntity::class, $result);
        static::assertInstanceOf(DateTime::class, $result->getCreated());
        static::assertSame(1, $result->getId());
        static::assertSame('Tom', $result->getUsername());
        static::assertSame('tom@example.com', $result->getEmail());
        static::assertSame(2, $result->getUuidEmbeds()->count());

        $firstEmbed = $result->getUuidEmbeds()->first();
        static::assertInstanceOf(EmbeddedUuidDummyEntity::class, $firstEmbed);
        static::assertSame('', $firstEmbed->getId());
        static::assertSame('', $firstEmbed->getType());
        static::assertTrue($firstEmbed->isActive());
    }

    /** @throws ExceptionInterface */
    public function testWithoutClassMap(): void
    {
        $data = [
            'id' => 1,
            'username' => 'Tom',
            'email' => 'tom@example.com',
            'uuidEmbeds' => ['a92fb3c0-5132-4ed7-96e3-b917dbabf681', 'f079b6da-3f5c-4655-850c-abd7ab22d65d'],
            'created' => '2022-08-20 14:20:54',
        ];

        $result = $this->createUuidSerializer('find')->denormalize($data, DummyEntity::class);

        static::assertInstanceOf(DummyEntity::class, $result);
        static::assertInstanceOf(DateTime::class, $result->getCreated());
        static::assertSame(1, $result->getId());
        static::assertSame('Tom', $result->getUsername());
        static::assertSame('tom@example.com', $result->getEmail());
        static::assertSame(2, $result->getUuidEmbeds()->count());

        $firstEmbed = $result->getUuidEmbeds()->first();
        static::assertInstanceOf(EmbeddedUuidDummyEntity::class, $firstEmbed);
        static::assertSame('', $firstEmbed->getId());
        static::assertSame('', $firstEmbed->getType());
        static::assertTrue($firstEmbed->isActive());
    }

    /** @throws ExceptionInterface */
    public function testWithMissingEntity(): void
    {
        static::expectException(NotNormalizableValueException::class);
        static::expectExceptionMessageMatches('/"null" given at property path "uuidEmbeds"\.$/');

        $data = [
            'id' => 1,
            'username' => 'Tom',
            'email' => 'tom@example.com',
            'uuidEmbeds' => ['a92fb3c0-5132-4ed7-96e3-b917dbabf681', 'f079b6da-cccc-bbbb-aaaa-abd7ab22d65d'],
            'created' => '2022-08-20 14:20:54',
        ];

        $this->createUuidSerializer('find')->denormalize($data, DummyEntity::class, null, [
            EntityUuidDenormalizer::CLASS_MAP => [EmbeddedUuidDummyEntity::class => 'find'],
        ]);
    }

    /** @throws ExceptionInterface */
    public function testWithUnknownHydratingMethod(): void
    {
        static::expectException(UnexpectedValueException::class);
        static::expectExceptionMessageMatches('/Trying to call \w+::fetchOne\([0-9a-f-]+\) failed\!/');

        $data = [
            'id' => 1,
            'username' => 'Tom',
            'email' => 'tom@example.com',
            'uuidEmbeds' => ['a92fb3c0-5132-4ed7-96e3-b917dbabf681', 'f079b6da-3f5c-4655-850c-abd7ab22d65d'],
            'created' => '2022-08-20 14:20:54',
        ];

        $this->createUuidSerializer('find')->denormalize($data, DummyEntity::class, null, [
            EntityUuidDenormalizer::CLASS_MAP => [EmbeddedUuidDummyEntity::class => 'fetchOne'],
        ]);
    }

    /** @throws ExceptionInterface */
    public function testWithUnknownClassName(): void
    {
        static::expectException(UnexpectedValueException::class);
        static::expectExceptionMessage('Got "App\Entity\UnknownEntity" which is not a valid class name!');

        $data = [
            'id' => 1,
            'username' => 'Tom',
            'email' => 'tom@example.com',
            'uuidEmbeds' => ['a92fb3c0-5132-4ed7-96e3-b917dbabf681', 'f079b6da-3f5c-4655-850c-abd7ab22d65d'],
            'created' => '2022-08-20 14:20:54',
        ];

        $this->createUuidSerializer('find')->denormalize($data, DummyEntity::class, null, [
            EntityUuidDenormalizer::CLASS_MAP => ['App\Entity\UnknownEntity'],
        ]);
    }

    /** @throws ExceptionInterface */
    public function testWithEmptyClassName(): void
    {
        static::expectException(UnexpectedValueException::class);
        static::expectExceptionMessage('Got "" which is not a valid class name!');

        $data = [
            'id' => 1,
            'username' => 'Tom',
            'email' => 'tom@example.com',
            'uuidEmbeds' => ['a92fb3c0-5132-4ed7-96e3-b917dbabf681', 'f079b6da-3f5c-4655-850c-abd7ab22d65d'],
            'created' => '2022-08-20 14:20:54',
        ];

        $this->createUuidSerializer('find')->denormalize($data, DummyEntity::class, null, [
            EntityUuidDenormalizer::CLASS_MAP => [''],
        ]);
    }

    /** @throws ExceptionInterface */
    public function testWithWrongHydratingMethod(): void
    {
        static::expectException(UnexpectedValueException::class);
        static::expectExceptionMessage('Got hydrating method "2" which is not valid!');

        $data = [
            'id' => 1,
            'username' => 'Tom',
            'email' => 'tom@example.com',
            'uuidEmbeds' => ['a92fb3c0-5132-4ed7-96e3-b917dbabf681', 'f079b6da-3f5c-4655-850c-abd7ab22d65d'],
            'created' => '2022-08-20 14:20:54',
        ];

        $this->createUuidSerializer('find')->denormalize($data, DummyEntity::class, null, [
            EntityUuidDenormalizer::CLASS_MAP => [EmbeddedUuidDummyEntity::class => 2],
        ]);
    }

    /** @throws ExceptionInterface */
    public function testWithComplexData(): void
    {
        $data = [
            'id' => 1,
            'username' => 'Tom',
            'email' => 'tom@example.com',
            'uuidEmbeds' => [
                [
                    'uuid' => 'a92fb3c0-5132-4ed7-96e3-b917dbabf681',
                    'type' => 't99',
                    'active' => false,
                ],
                [
                    'uuid' => 'f079b6da-3f5c-4655-850c-abd7ab22d65d',
                    'type' => 't4',
                    'active' => true,
                ],
            ],
            'created' => '2022-08-20 14:20:54',
        ];

        $result = $this->createUuidSerializer('find')->denormalize($data, DummyEntity::class, null, [
            EntityUuidDenormalizer::CLASS_MAP => [EmbeddedUuidDummyEntity::class => 'find'],
        ]);

        static::assertInstanceOf(DummyEntity::class, $result);
        static::assertInstanceOf(DateTime::class, $result->getCreated());
        static::assertSame(1, $result->getId());
        static::assertSame('Tom', $result->getUsername());
        static::assertSame('tom@example.com', $result->getEmail());
        static::assertSame(2, $result->getUuidEmbeds()->count());

        $firstEmbed = $result->getUuidEmbeds()->first();
        static::assertInstanceOf(EmbeddedUuidDummyEntity::class, $firstEmbed);
        static::assertSame('a92fb3c0-5132-4ed7-96e3-b917dbabf681', $firstEmbed->getId());
        static::assertSame('t99', $firstEmbed->getType());
        static::assertFalse($firstEmbed->isActive());
    }

    /** @throws ExceptionInterface */
    public function testWithMissingComplexEntity(): void
    {
        static::expectException(NotNormalizableValueException::class);
        static::expectExceptionMessageMatches('/"null" given at property path "uuidEmbeds"\.$/');

        $data = [
            'id' => 1,
            'username' => 'Tom',
            'email' => 'tom@example.com',
            'uuidEmbeds' => [
                [
                    'uuid' => 'a92fb3c0-5132-4ed7-96e3-b917dbabf681',
                    'type' => 't99',
                    'active' => false,
                ],
                [
                    'uuid' => 'f079b6da-cccc-bbbb-aaaa-abd7ab22d65d',
                    'type' => 't4',
                    'active' => true,
                ],
            ],
            'created' => '2022-08-20 14:20:54',
        ];

        $this->createUuidSerializer('find')->denormalize($data, DummyEntity::class, null, [
            EntityUuidDenormalizer::CLASS_MAP => [EmbeddedUuidDummyEntity::class => 'find'],
        ]);
    }
}
