<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Normalizer;

use DateTime;
use PHPUnit\Framework\TestCase;
use SkriptManufaktur\SimpleRestBundle\Component\EntityIdDenormalizer;
use SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DummyEntity;
use SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\EmbeddedDummyEntity;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

class EntityIdNormalizerTest extends TestCase
{
    use SerializerMockingTrait;

    private array $embeddeds;


    protected function setUp(): void
    {
        parent::setUp();

        $this->embeddeds = [
            (new EmbeddedDummyEntity())
                ->setId(3)
                ->setType('t1')
                ->setActive(true)
            ,
            (new EmbeddedDummyEntity())
                ->setId(4)
                ->setType('t2')
                ->setActive(true)
            ,
            (new EmbeddedDummyEntity())
                ->setId(5)
                ->setType('t3')
                ->setActive(true)
            ,
            (new EmbeddedDummyEntity())
                ->setId(6)
                ->setType('t1')
                ->setActive(false)
            ,
        ];
    }

    public function testMock(): void
    {
        $registry = $this->createManagerRegistry('find', EmbeddedDummyEntity::class, function (int $id) {
            $matching = array_filter($this->embeddeds, fn (EmbeddedDummyEntity $e) => $e->getId() === $id);

            if (empty($matching)) {
                return null;
            }

            return reset($matching);
        });

        $manager = $registry->getManagerForClass(EmbeddedDummyEntity::class);
        $repository = $manager->getRepository(EmbeddedDummyEntity::class);
        $entity = $repository->find(5);

        static::assertInstanceOf(EmbeddedDummyEntity::class, $entity);
        static::assertSame(5, $entity->getId());
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
            'embeds' => [4, 6],
            'created' => '2022-08-20 14:20:54',
        ];

        $result = $this->createIdSerializer('find')->denormalize($data, DummyEntity::class, null, [
            EntityIdDenormalizer::CLASS_MAP => [EmbeddedDummyEntity::class => 'find'],
        ]);

        static::assertInstanceOf(DummyEntity::class, $result);
        static::assertInstanceOf(DateTime::class, $result->getCreated());
        static::assertSame(1, $result->getId());
        static::assertSame('Tom', $result->getUsername());
        static::assertSame('tom@example.com', $result->getEmail());
        static::assertSame(2, $result->getEmbeds()->count());

        $firstEmbed = $result->getEmbeds()->first();
        static::assertInstanceOf(EmbeddedDummyEntity::class, $firstEmbed);
        static::assertSame(4, $firstEmbed->getId());
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

        $result = $this->createIdSerializer('find')->denormalize($data, DummyEntity::class, null, [
            EntityIdDenormalizer::CLASS_MAP => [EmbeddedDummyEntity::class => 'find'],
        ]);

        static::assertInstanceOf(DummyEntity::class, $result);
        static::assertInstanceOf(DateTime::class, $result->getCreated());
        static::assertSame(1, $result->getId());
        static::assertSame('Tom', $result->getUsername());
        static::assertSame('tom@example.com', $result->getEmail());
        static::assertTrue($result->getEmbeds()->isEmpty());
    }

    /** @throws ExceptionInterface */
    public function testWithSimpleClassMap(): void
    {
        $data = [
            'id' => 1,
            'username' => 'Tom',
            'email' => 'tom@example.com',
            'embeds' => [4, 6],
            'created' => '2022-08-20 14:20:54',
        ];

        $result = $this->createIdSerializer('find')->denormalize($data, DummyEntity::class, null, [
            EntityIdDenormalizer::CLASS_MAP => [EmbeddedDummyEntity::class],
        ]);

        static::assertInstanceOf(DummyEntity::class, $result);
        static::assertInstanceOf(DateTime::class, $result->getCreated());
        static::assertSame(1, $result->getId());
        static::assertSame('Tom', $result->getUsername());
        static::assertSame('tom@example.com', $result->getEmail());
        static::assertSame(2, $result->getEmbeds()->count());

        $firstEmbed = $result->getEmbeds()->first();
        static::assertInstanceOf(EmbeddedDummyEntity::class, $firstEmbed);
        static::assertSame(4, $firstEmbed->getId());
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
            'embeds' => [4, 6],
            'created' => '2022-08-20 14:20:54',
        ];

        $result = $this->createIdSerializer('getOne')->denormalize($data, DummyEntity::class, null, [
            EntityIdDenormalizer::CLASS_MAP => [EmbeddedDummyEntity::class => 'getOne'],
        ]);

        static::assertInstanceOf(DummyEntity::class, $result);
        static::assertInstanceOf(DateTime::class, $result->getCreated());
        static::assertSame(1, $result->getId());
        static::assertSame('Tom', $result->getUsername());
        static::assertSame('tom@example.com', $result->getEmail());
        static::assertSame(2, $result->getEmbeds()->count());

        $firstEmbed = $result->getEmbeds()->first();
        static::assertInstanceOf(EmbeddedDummyEntity::class, $firstEmbed);
        static::assertSame(4, $firstEmbed->getId());
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
            'embeds' => [4, 6],
            'created' => '2022-08-20 14:20:54',
        ];

        $result = $this->createIdSerializer('find')->denormalize($data, DummyEntity::class, null, [
            EntityIdDenormalizer::CLASS_MAP => [],
        ]);

        static::assertInstanceOf(DummyEntity::class, $result);
        static::assertInstanceOf(DateTime::class, $result->getCreated());
        static::assertSame(1, $result->getId());
        static::assertSame('Tom', $result->getUsername());
        static::assertSame('tom@example.com', $result->getEmail());
        static::assertSame(2, $result->getEmbeds()->count());

        $firstEmbed = $result->getEmbeds()->first();
        static::assertInstanceOf(EmbeddedDummyEntity::class, $firstEmbed);
        static::assertSame(0, $firstEmbed->getId());
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
            'embeds' => [4, 6],
            'created' => '2022-08-20 14:20:54',
        ];

        $result = $this->createIdSerializer('find')->denormalize($data, DummyEntity::class);

        static::assertInstanceOf(DummyEntity::class, $result);
        static::assertInstanceOf(DateTime::class, $result->getCreated());
        static::assertSame(1, $result->getId());
        static::assertSame('Tom', $result->getUsername());
        static::assertSame('tom@example.com', $result->getEmail());
        static::assertSame(2, $result->getEmbeds()->count());

        $firstEmbed = $result->getEmbeds()->first();
        static::assertInstanceOf(EmbeddedDummyEntity::class, $firstEmbed);
        static::assertSame(0, $firstEmbed->getId());
        static::assertSame('', $firstEmbed->getType());
        static::assertTrue($firstEmbed->isActive());
    }

    /** @throws ExceptionInterface */
    public function testWithMissingEntity(): void
    {
        static::expectException(NotNormalizableValueException::class);
        static::expectExceptionMessageMatches('/"null" given at property path "embeds"\.$/');

        $data = [
            'id' => 1,
            'username' => 'Tom',
            'email' => 'tom@example.com',
            'embeds' => [4, 12],
            'created' => '2022-08-20 14:20:54',
        ];

        $this->createIdSerializer('find')->denormalize($data, DummyEntity::class, null, [
            EntityIdDenormalizer::CLASS_MAP => [EmbeddedDummyEntity::class => 'find'],
        ]);
    }

    /** @throws ExceptionInterface */
    public function testWithUnknownHydratingMethod(): void
    {
        static::expectException(UnexpectedValueException::class);
        static::expectExceptionMessageMatches('/Trying to call \w+::fetchOne\(\d\) failed\!/');

        $data = [
            'id' => 1,
            'username' => 'Tom',
            'email' => 'tom@example.com',
            'embeds' => [4, 6],
            'created' => '2022-08-20 14:20:54',
        ];

        $this->createIdSerializer('find')->denormalize($data, DummyEntity::class, null, [
            EntityIdDenormalizer::CLASS_MAP => [EmbeddedDummyEntity::class => 'fetchOne'],
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
            'embeds' => [4, 6],
            'created' => '2022-08-20 14:20:54',
        ];

        $this->createIdSerializer('find')->denormalize($data, DummyEntity::class, null, [
            EntityIdDenormalizer::CLASS_MAP => ['App\Entity\UnknownEntity'],
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
            'embeds' => [4, 6],
            'created' => '2022-08-20 14:20:54',
        ];

        $this->createIdSerializer('find')->denormalize($data, DummyEntity::class, null, [
            EntityIdDenormalizer::CLASS_MAP => [''],
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
            'embeds' => [4, 6],
            'created' => '2022-08-20 14:20:54',
        ];

        $this->createIdSerializer('find')->denormalize($data, DummyEntity::class, null, [
            EntityIdDenormalizer::CLASS_MAP => [EmbeddedDummyEntity::class => 2],
        ]);
    }

    /** @throws ExceptionInterface */
    public function testWithComplexData(): void
    {
        $data = [
            'id' => 1,
            'username' => 'Tom',
            'email' => 'tom@example.com',
            'embeds' => [
                [
                    'id' => 4,
                    'type' => 't99',
                    'active' => false,
                ],
                [
                    'id' => 6,
                    'type' => 't4',
                    'active' => true,
                ],
            ],
            'created' => '2022-08-20 14:20:54',
        ];

        $result = $this->createIdSerializer('find')->denormalize($data, DummyEntity::class, null, [
            EntityIdDenormalizer::CLASS_MAP => [EmbeddedDummyEntity::class => 'find'],
        ]);

        static::assertInstanceOf(DummyEntity::class, $result);
        static::assertInstanceOf(DateTime::class, $result->getCreated());
        static::assertSame(1, $result->getId());
        static::assertSame('Tom', $result->getUsername());
        static::assertSame('tom@example.com', $result->getEmail());
        static::assertSame(2, $result->getEmbeds()->count());

        $firstEmbed = $result->getEmbeds()->first();
        static::assertInstanceOf(EmbeddedDummyEntity::class, $firstEmbed);
        static::assertSame(4, $firstEmbed->getId());
        static::assertSame('t99', $firstEmbed->getType());
        static::assertFalse($firstEmbed->isActive());
    }

    /** @throws ExceptionInterface */
    public function testWithMissingComplexEntity(): void
    {
        static::expectException(NotNormalizableValueException::class);
        static::expectExceptionMessageMatches('/"null" given at property path "embeds"\.$/');

        $data = [
            'id' => 1,
            'username' => 'Tom',
            'email' => 'tom@example.com',
            'embeds' => [
                [
                    'id' => 4,
                    'type' => 't99',
                    'active' => false,
                ],
                [
                    'id' => 12,
                    'type' => 't4',
                    'active' => true,
                ],
            ],
            'created' => '2022-08-20 14:20:54',
        ];

        $this->createIdSerializer('find')->denormalize($data, DummyEntity::class, null, [
            EntityIdDenormalizer::CLASS_MAP => [EmbeddedDummyEntity::class => 'find'],
        ]);
    }
}
