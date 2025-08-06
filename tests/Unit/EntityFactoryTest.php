<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Unit;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use SkriptManufaktur\SimpleRestBundle\Component\ServiceEntityFactory;
use SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DummyEntity;

class EntityFactoryTest extends TestCase
{
    public function testAddSimpleComparison(): void
    {
        $qb = $this->createQueryBuilder();
        $wrapper = $this->createFactoryWrapper();
        $result = 'SELECT t FROM DummyUser t WHERE t.username = :username_1';

        $wrapper->addComparisonProxy($qb, 'username', 'muster');

        static::assertInstanceOf(QueryBuilder::class, $qb);
        static::assertIsString($qb->getDQL());
        static::assertSame($result, $qb->getDQL());
        static::assertCount(1, $qb->getParameters());
        static::assertSame(['username_1' => 'muster'], $this->unwrapParameters($qb->getParameters()));
    }

    public function testAddArrayComparison(): void
    {
        $qb = $this->createQueryBuilder();
        $wrapper = $this->createFactoryWrapper();
        $result = 'SELECT t FROM DummyUser t WHERE t.username IN (:username_1)';
        $resultParams = [
            0 => 'muster',
            2 => 4,
            3 => 1,
        ];

        $wrapper->addComparisonProxy($qb, 'username', [
            'muster',
            'undefined',
            4,
            true,
        ]);

        static::assertInstanceOf(QueryBuilder::class, $qb);
        static::assertIsString($qb->getDQL());
        static::assertSame($result, $qb->getDQL());
        static::assertCount(1, $qb->getParameters());
        static::assertSame(['username_1' => $resultParams], $this->unwrapParameters($qb->getParameters()));
    }

    public function testAddNullComparison(): void
    {
        $qb = $this->createQueryBuilder();
        $wrapper = $this->createFactoryWrapper();
        $result = 'SELECT t FROM DummyUser t WHERE t.username = :username_1';

        $wrapper->addComparisonProxy($qb, 'username', null);

        static::assertInstanceOf(QueryBuilder::class, $qb);
        static::assertIsString($qb->getDQL());
        static::assertSame($result, $qb->getDQL());
        static::assertCount(1, $qb->getParameters());
        static::assertSame(['username_1' => null], $this->unwrapParameters($qb->getParameters()));
    }

    public function testAddStringSearchExact(): void
    {
        $qb = $this->createQueryBuilder();
        $wrapper = $this->createFactoryWrapper();
        $result = 'SELECT t FROM DummyUser t WHERE t.username LIKE :username_1 AND LOWER(t.email) LIKE :email_2';

        $wrapper->addTextSearchProxy(
            qb: $qb,
            field: 'username',
            value: 'acme',
            filterType: ServiceEntityFactory::FILTER_EXACT,
            caseSensitive: true
        );
        $wrapper->addTextSearchProxy(
            qb: $qb,
            field: 'email',
            value: 'MUSTER@example.com',
            filterType: ServiceEntityFactory::FILTER_EXACT,
            caseSensitive: false
        );

        static::assertInstanceOf(QueryBuilder::class, $qb);
        static::assertIsString($qb->getDQL());
        static::assertSame($result, $qb->getDQL());
        static::assertCount(2, $qb->getParameters());
        static::assertSame(
            ['username_1' => 'acme', 'email_2' => 'muster@example.com'],
            $this->unwrapParameters($qb->getParameters())
        );
    }

    public function testAddPartialStringSearch(): void
    {
        $qb = $this->createQueryBuilder();
        $wrapper = $this->createFactoryWrapper();
        $result = 'SELECT t FROM DummyUser t WHERE t.username LIKE CONCAT(\'%\', :username_1, \'%\') '
            .'AND LOWER(t.email) LIKE CONCAT(\'%\', :email_2, \'%\')';

        $wrapper->addTextSearchProxy(
            qb: $qb,
            field: 'username',
            value: 'acme',
            filterType: ServiceEntityFactory::FILTER_PARTIAL,
            caseSensitive: true
        );
        $wrapper->addTextSearchProxy(
            qb: $qb,
            field: 'email',
            value: 'MUSTER@example.com',
            filterType: ServiceEntityFactory::FILTER_PARTIAL,
            caseSensitive: false
        );

        static::assertInstanceOf(QueryBuilder::class, $qb);
        static::assertIsString($qb->getDQL());
        static::assertSame($result, $qb->getDQL());
        static::assertCount(2, $qb->getParameters());
        static::assertSame(
            ['username_1' => 'acme', 'email_2' => 'muster@example.com'],
            $this->unwrapParameters($qb->getParameters())
        );
    }

    public function testAddSimpleDateSearch(): void
    {
        $qb = $this->createQueryBuilder();
        $wrapper = $this->createFactoryWrapper();
        $result = 'SELECT t FROM DummyUser t WHERE t.created = :created_eq_1';

        $wrapper->addDateSearchProxy($qb, 'created', '2024-08-15T20:20:00', false);

        static::assertInstanceOf(QueryBuilder::class, $qb);
        static::assertIsString($qb->getDQL());
        static::assertSame($result, $qb->getDQL());
        static::assertCount(1, $qb->getParameters());
        static::assertSame(['created_eq_1' => '2024-08-15 20:20:00'], $this->unwrapParameters($qb->getParameters()));
    }

    public function testAddNullDateSearch(): void
    {
        $qb = $this->createQueryBuilder();
        $wrapper = $this->createFactoryWrapper();
        $result = 'SELECT t FROM DummyUser t WHERE t.created IS NULL AND t.deleted IS NULL';

        $wrapper->addDateSearchProxy($qb, 'created', 'null', true);
        $wrapper->addDateSearchProxy($qb, 'deleted', null, true);
        $wrapper->addDateSearchProxy($qb, 'nonsense', 'undefined', false);

        static::assertInstanceOf(QueryBuilder::class, $qb);
        static::assertIsString($qb->getDQL());
        static::assertSame($result, $qb->getDQL());
        static::assertCount(0, $qb->getParameters());
    }

    public function testAddComplexDateSearch(): void
    {
        $qb = $this->createQueryBuilder();
        $wrapper = $this->createFactoryWrapper();
        $result = 'SELECT t FROM DummyUser t WHERE t.created >= :created_gte_1 AND t.created <= :created_lte_2 '
            .'AND t.deleted IS NULL';

        $wrapper->addDateSearchProxy($qb, 'created', ['gte' => '2024-08-15T20:20:00', 'lte' => ['2024-08-20T10:30:00']], true);
        $wrapper->addDateSearchProxy($qb, 'deleted', ['eq' => [null]], true);

        static::assertInstanceOf(QueryBuilder::class, $qb);
        static::assertIsString($qb->getDQL());
        static::assertSame($result, $qb->getDQL());
        static::assertCount(2, $qb->getParameters());
        static::assertSame(
            ['created_gte_1' => '2024-08-15 20:20:00', 'created_lte_2' => '2024-08-20 10:30:00'],
            $this->unwrapParameters($qb->getParameters())
        );
    }

    public function testAddInvalidDateSearch(): void
    {
        $qb = $this->createQueryBuilder();
        $wrapper = $this->createFactoryWrapper();
        $result = 'SELECT t FROM DummyUser t';

        $wrapper->addDateSearchProxy($qb, 'created', '', false);
        $wrapper->addDateSearchProxy($qb, 'deleted', [], false);
        $wrapper->addDateSearchProxy($qb, 'nonsense', 'undefined', false);
        $wrapper->addDateSearchProxy($qb, 'dueDate', ['gt' => 'undefined'], false);
        $wrapper->addDateSearchProxy($qb, 'dueTime', null, false);

        static::assertInstanceOf(QueryBuilder::class, $qb);
        static::assertIsString($qb->getDQL());
        static::assertSame($result, $qb->getDQL());
        static::assertCount(0, $qb->getParameters());
    }

    public function testAddSimpleNumberSearch(): void
    {
        $qb = $this->createQueryBuilder();
        $wrapper = $this->createFactoryWrapper();
        $result = 'SELECT t FROM DummyUser t WHERE t.completed = :completed_eq_1';

        $wrapper->addNumberSearchProxy($qb, 'completed', 50, false);
        $wrapper->addNumberSearchProxy($qb, 'nonsense', null, false);

        static::assertInstanceOf(QueryBuilder::class, $qb);
        static::assertIsString($qb->getDQL());
        static::assertSame($result, $qb->getDQL());
        static::assertCount(1, $qb->getParameters());
        static::assertSame(['completed_eq_1' => 50], $this->unwrapParameters($qb->getParameters()));
    }

    public function testAddNullNumberSearch(): void
    {
        $qb = $this->createQueryBuilder();
        $wrapper = $this->createFactoryWrapper();
        $result = 'SELECT t FROM DummyUser t WHERE t.completed IS NULL AND t.number IS NULL';

        $wrapper->addNumberSearchProxy($qb, 'completed', null, true);
        $wrapper->addNumberSearchProxy($qb, 'number', ['eq' => [null]], true);

        static::assertInstanceOf(QueryBuilder::class, $qb);
        static::assertIsString($qb->getDQL());
        static::assertSame($result, $qb->getDQL());
        static::assertCount(0, $qb->getParameters());
    }

    public function testAddComplexNumberSearch(): void
    {
        $qb = $this->createQueryBuilder();
        $wrapper = $this->createFactoryWrapper();
        $result = 'SELECT t FROM DummyUser t WHERE t.completed >= :completed_gte_1 AND t.completed <= :completed_lte_2 '
            .'AND t.number >= :number_gte_3 AND t.number <= :number_lte_4 AND t.deletedAt IS NULL';

        $wrapper->addNumberSearchProxy($qb, 'completed', ['gte' => '10', 'lte' => [80]], false);
        $wrapper->addNumberSearchProxy($qb, 'number', ['gte' => '20,1', 'lte' => [80.0]], false);
        $wrapper->addNumberSearchProxy($qb, 'deletedAt', ['eq' => 'null'], true);

        static::assertInstanceOf(QueryBuilder::class, $qb);
        static::assertIsString($qb->getDQL());
        static::assertSame($result, $qb->getDQL());
        static::assertCount(4, $qb->getParameters());
        static::assertSame(
            ['completed_gte_1' => 10, 'completed_lte_2' => 80, 'number_gte_3' => 20.1, 'number_lte_4' => 80.0],
            $this->unwrapParameters($qb->getParameters())
        );
    }

    public function testAddInvalidNumberSearch(): void
    {
        $qb = $this->createQueryBuilder();
        $wrapper = $this->createFactoryWrapper();
        $result = 'SELECT t FROM DummyUser t';

        $wrapper->addNumberSearchProxy($qb, 'completed', '', false);
        $wrapper->addNumberSearchProxy($qb, 'nonsense', 'undefined', false);
        $wrapper->addNumberSearchProxy($qb, 'deletedAt', ['gt' => 'undefined'], false);

        static::assertInstanceOf(QueryBuilder::class, $qb);
        static::assertIsString($qb->getDQL());
        static::assertSame($result, $qb->getDQL());
        static::assertCount(0, $qb->getParameters());
    }

    public function testAddSimpleBoolSearch(): void
    {
        $qb = $this->createQueryBuilder();
        $wrapper = $this->createFactoryWrapper();
        $result = 'SELECT t FROM DummyUser t WHERE t.active = :active_1 AND t.verified = :verified_2';

        $wrapper->addBoolSearchProxy($qb, 'active', true, false);
        $wrapper->addBoolSearchProxy($qb, 'verified', 'f', false);

        static::assertInstanceOf(QueryBuilder::class, $qb);
        static::assertIsString($qb->getDQL());
        static::assertSame($result, $qb->getDQL());
        static::assertCount(2, $qb->getParameters());
        static::assertSame(['active_1' => 1, 'verified_2' => 0], $this->unwrapParameters($qb->getParameters()));
    }

    public function testAddNullBoolSearch(): void
    {
        $qb = $this->createQueryBuilder();
        $wrapper = $this->createFactoryWrapper();
        $result = 'SELECT t FROM DummyUser t WHERE t.active IS NULL AND t.verified IS NULL';

        $wrapper->addBoolSearchProxy($qb, 'active', null, true);
        $wrapper->addBoolSearchProxy($qb, 'verified', ['null'], true);

        static::assertInstanceOf(QueryBuilder::class, $qb);
        static::assertIsString($qb->getDQL());
        static::assertSame($result, $qb->getDQL());
        static::assertCount(0, $qb->getParameters());
    }

    public function testAddComplexBoolSearch(): void
    {
        $qb = $this->createQueryBuilder();
        $wrapper = $this->createFactoryWrapper();
        $result = 'SELECT t FROM DummyUser t WHERE t.active = :active_1 AND t.verified = :verified_2';

        $wrapper->addBoolSearchProxy($qb, 'active', ['true', 'false'], false);
        $wrapper->addBoolSearchProxy($qb, 'number', [], false);
        $wrapper->addBoolSearchProxy($qb, 'nonsense', ['undefined'], false);
        $wrapper->addBoolSearchProxy($qb, 'verified', [15, 0], true);

        static::assertInstanceOf(QueryBuilder::class, $qb);
        static::assertIsString($qb->getDQL());
        static::assertSame($result, $qb->getDQL());
        static::assertCount(2, $qb->getParameters());
        static::assertSame(['active_1' => 1, 'verified_2' => 1], $this->unwrapParameters($qb->getParameters()));
    }

    public function testAddInvalidBoolSearch(): void
    {
        $qb = $this->createQueryBuilder();
        $wrapper = $this->createFactoryWrapper();
        $result = 'SELECT t FROM DummyUser t';

        $wrapper->addBoolSearchProxy($qb, 'nonsense1', '', false);
        $wrapper->addBoolSearchProxy($qb, 'nonsense2', 'undefined', false);
        $wrapper->addBoolSearchProxy($qb, 'nonsense3', ['undefined'], false);
        $wrapper->addBoolSearchProxy($qb, 'nonsense4', [''], false);
        $wrapper->addBoolSearchProxy($qb, 'nonsense5', ['undefined', 1], false);
        $wrapper->addBoolSearchProxy($qb, 'nonsense6', ['undefined', 15], false);

        static::assertInstanceOf(QueryBuilder::class, $qb);
        static::assertIsString($qb->getDQL());
        static::assertSame($result, $qb->getDQL());
        static::assertCount(0, $qb->getParameters());
    }

    private function createQueryBuilder(): QueryBuilder
    {
        $emMock = self::createMock(EntityManagerInterface::class);

        $qb = new QueryBuilder($emMock);
        $qb->select('t')->from('DummyUser', 't');

        return $qb;
    }

    private function createFactoryWrapper(): ServiceEntityFactory
    {
        $mrMock = self::createMock(ManagerRegistry::class);

        return new class($mrMock, DummyEntity::class) extends ServiceEntityFactory
        {
            public function __construct(ManagerRegistry $registry, string $entityClass)
            {
                parent::__construct($registry, $entityClass);
            }

            public function addComparisonProxy(QueryBuilder $qb, string $field, string|float|int|array|null $value): void
            {
                $this->addComparison($qb, $field, $value);
            }

            public function addTextSearchProxy(QueryBuilder $qb, string $field, string|array|null $value, string $filterType, bool $caseSensitive): void
            {
                $this->addStringSearchTo($qb, $field, $value, $filterType, $caseSensitive);
            }

            public function addDateSearchProxy(QueryBuilder $qb, string $field, string|array|null $value, bool $allowNull): void
            {
                $this->addDateSearchTo($qb, $field, $value, $allowNull);
            }

            public function addNumberSearchProxy(QueryBuilder $qb, string $field, string|int|float|array|null $value, bool $allowNull): void
            {
                $this->addNumberSearchTo($qb, $field, $value, $allowNull);
            }

            public function addBoolSearchProxy(QueryBuilder $qb, string $field, string|bool|array|null $value, bool $allowNull): void
            {
                $this->addBooleanSearchTo($qb, $field, $value, $allowNull);
            }
        };
    }

    private function unwrapParameters(ArrayCollection $parameters): array
    {
        $output = [];

        /** @var Parameter $param */
        foreach ($parameters as $param) {
            $output[$param->getName()] = $param->getValue();
        }

        return $output;
    }
}
