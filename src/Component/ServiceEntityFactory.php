<?php

namespace SkriptManufaktur\SimpleRestBundle\Component;

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use RuntimeException;

/**
 * @template T of object
 *
 * @template-extends ServiceEntityRepository<T>
 */
class ServiceEntityFactory extends ServiceEntityRepository
{
    use DoctrineTransformerTrait;

    public const string FILTER_EXACT = 'filter_exact';
    public const string FILTER_START = 'filter_start';
    public const string FILTER_END = 'filter_end';
    public const string FILTER_PARTIAL = 'filter_partial';
    public const string FILTER_WORD_START = 'filter_word_start';

    private int $counter = 1;


    public function __construct(ManagerRegistry $registry, string $entityClass)
    {
        parent::__construct($registry, $entityClass);
    }

    /**
     * @return ServiceEntityFactory<T>
     *
     * @throws Exception
     */
    public function flush(): self
    {
        $this->getEntityManager()->flush();

        return $this;
    }

    /**
     * @param object $data
     *
     * @return ServiceEntityFactory<T>
     *
     * @throws Exception
     */
    protected function factoryPersist(object $data): self
    {
        $em = $this->getEntityManager();

        if (!$em->contains($data) || $this->isDeferredExplicit($em, $data)) {
            $em->persist($data);
        }

        return $this;
    }

    /**
     * @param object $data
     *
     * @return ServiceEntityFactory<T>
     *
     * @throws Exception
     */
    protected function factoryRemove(object $data): self
    {
        $this->getEntityManager()->remove($data);

        return $this;
    }

    /**
     * @param object $data
     *
     * @return ServiceEntityFactory<T>
     *
     * @throws Exception|ORMException
     */
    protected function factoryRefresh(object $data): self
    {
        $this->getEntityManager()->refresh($data);

        return $this;
    }

    /**
     * @param object $data
     *
     * @return ServiceEntityFactory<T>
     *
     * @throws Exception|ORMException
     */
    protected function factoryPersistAndFlush(object $data): self
    {
        $this->factoryPersist($data);
        $this->flush();

        $this->getEntityManager()->refresh($data);

        return $this;
    }

    /**
     * @param object $data
     *
     * @return ServiceEntityFactory<T>
     *
     * @throws Exception
     */
    protected function factoryRemoveAndFlush(object $data): self
    {
        $this->factoryRemove($data);
        $this->flush();

        return $this;
    }

    /**
     * Adding a simple comparison to QueryBuilder
     *
     * @param QueryBuilder                               $qb
     * @param string                                     $field The field's name to find in filter and match in database
     * @param string|float|int|(string|float|int)[]|null $value
     */
    protected function addComparison(QueryBuilder $qb, string $field, string|float|int|array|null $value): void
    {
        // if nothing was passed, just return
        if ('undefined' === $value || 'null' === $value) {
            return;
        }

        $varName = $this->unfoldQueryField($field);
        $parameterName = sprintf('%s_%s', str_replace(['.', ' '], '_', $field), $this->counter++);

        if (is_array($value) && !empty($value)) {
            $qb->andWhere(sprintf('%s IN (:%s)', $varName, $parameterName));
            $qb->setParameter($parameterName, array_map(
                fn (string|float|int $item): string|float|int => is_string($item) ? trim($item) : $item,
                array_filter(
                    $value,
                    fn (string|float|int|null $item): bool => 'undefined' !== $item && 'null' !== $item
                )
            ));

            return;
        }

        $qb->andWhere(sprintf('%s = :%s', $varName, $parameterName));
        $qb->setParameter($parameterName, $value);
    }

    /**
     * Adds an ->andWhere() statement for string search to a QueryBuilder
     *
     * @param QueryBuilder                $qb
     * @param string                      $field
     * @param string|(string|null)[]|null $value
     * @param string                      $filterType
     * @param bool                        $caseSensitive
     */
    protected function addStringSearchTo(QueryBuilder $qb, string $field, string|array|null $value, string $filterType = self::FILTER_EXACT, bool $caseSensitive = true): void
    {
        // if nothing was passed, just return
        if (empty($value)) {
            return;
        }

        $varName = $this->unfoldQueryField($field);
        $values = is_string($value) ? [$value] : $value;
        $values = array_filter($values, fn (mixed $item): bool => is_string($item) && '' !== $item);

        if (!$caseSensitive) {
            $varName = sprintf('LOWER(%s)', $varName);
            $values = array_map(fn (string $item): string => mb_strtolower($item, 'UTF-8'), $values);
        }

        foreach ($values as $v) {
            $parameterName = sprintf('%s_%s', str_replace(['.', ' '], '_', $field), $this->counter++);
            $statement = match ($filterType) {
                self::FILTER_EXACT => sprintf('%s LIKE :%s', $varName, $parameterName),
                self::FILTER_PARTIAL => sprintf('%s LIKE CONCAT(\'%%\', :%s, \'%%\')', $varName, $parameterName),
                self::FILTER_START => sprintf('%s LIKE CONCAT(:%s, \'%%\')', $varName, $parameterName),
                self::FILTER_END => sprintf('%s LIKE CONCAT(\'%%\', :%s)', $varName, $parameterName),
                self::FILTER_WORD_START => sprintf('%1$s LIKE CONCAT(:%2$s, \'%%\') OR %1$s LIKE CONCAT(\'%% \', :%2$s, \'%%\')', $varName, $parameterName),
                default => throw new RuntimeException(sprintf('Filter-Type "%s" is unknown!', $filterType)),
            };

            $qb->andWhere($statement);
            $qb->setParameter($parameterName, $v);
        }
    }

    /**
     * Adds an ->andWhere() statement for date(-time) search in a QueryBuilder
     * Use like ?created[gte]=2020-06-20T20:00 (with time or just without)
     *
     * @param QueryBuilder                                           $qb
     * @param string                                                 $field
     * @param string|array<string, string|(string|null)[]|null>|null $value
     * @param bool                                                   $allowNull
     *
     * @throws Exception
     */
    protected function addDateSearchTo(QueryBuilder $qb, string $field, string|array|null $value, bool $allowNull = false): void
    {
        // if nothing was passed, just return
        if (!$allowNull && null === $value || is_array($value) && empty($value) || '' === $value || 'undefined' === $value) {
            return;
        }

        $valueIsArray = is_array($value);
        $varName = $this->unfoldQueryField($field);

        // error on falsy value
        if ($valueIsArray && !isset($value['eq']) && !isset($value['gt']) && !isset($value['lt']) && !isset($value['gte']) && !isset($value['lte'])) {
            throw new RuntimeException('Searching a date has to follow the format "?date[f]=value", where f is one of [eq, gt, lt, gte, lte].');
        }

        // when getting a string like ?date=2020-09-01, make it an equals comparison
        if (!$valueIsArray) {
            $value = [
                'eq' => $value,
            ];
        }

        // setup date controls from array
        foreach ($value as $operator => $datesArray) {
            $dates = is_array($datesArray) ? $datesArray : [$datesArray];

            foreach ($dates as $date) {
                // continue if null not allowed here
                if (!$allowNull && (null === $date || 'null' === $date)) {
                    continue;
                }

                // catch missing values, probably from JS
                if ('' === $date || 'undefined' === $date) {
                    continue;
                }

                // we have a special case with NULL in PostgreSQL
                if ('null' === $date || null === $date) {
                    $qb->andWhere(sprintf('%s IS NULL', $varName));
                    continue;
                }

                $parameterName = sprintf('%s_%s_%s', str_replace(['.', ' '], '_', $field), $operator, $this->counter++);
                $format = str_contains($date, 'T') ? 'Y-m-d H:i:s' : 'Y-m-d';
                $sign = match ($operator) {
                    'gte' => '>=',
                    'lte' => '<=',
                    'gt' => '>',
                    'lt' => '<',
                    default => '=',
                };

                $qb->andWhere(sprintf('%s %s :%s', $varName, $sign, $parameterName));
                $qb->setParameter($parameterName, new DateTime($date)->format($format));
            }
        }
    }

    /**
     * Adds an ->andWhere() statement for numeric search in a QueryBuilder
     * Use like ?priority[gte]=1&priority[lte]=4
     *
     * @param QueryBuilder                                                                         $qb
     * @param string                                                                               $field
     * @param string|int|float|array<string, string|int|float|null|(string|int|float|null)[]>|null $value
     * @param bool                                                                                 $allowNull
     *
     * @throws Exception
     */
    protected function addNumberSearchTo(QueryBuilder $qb, string $field, string|int|float|array|null $value, bool $allowNull = false): void
    {
        $valueIsArray = is_array($value);

        // if nothing was passed, just return
        if (!$allowNull && null === $value || $valueIsArray && empty($value) || '' === $value || 'undefined' === $value) {
            return;
        }

        $varName = $this->unfoldQueryField($field);

        // error on falsy value
        if ($valueIsArray && !isset($value['eq']) && !isset($value['gt']) && !isset($value['lt']) && !isset($value['gte']) && !isset($value['lte'])) {
            throw new RuntimeException('Searching a number has to follow the format "?number[f]=value", where f is one of [eq, gt, lt, gte, lte].');
        }

        // when getting a string like ?number=5, make it an equals comparison
        if (!$valueIsArray) {
            $value = [
                'eq' => $value,
            ];
        }

        // setup date controls from array
        foreach ($value as $operator => $numbersArray) {
            $numbers = is_array($numbersArray) ? $numbersArray : [$numbersArray];

            foreach ($numbers as $num) {
                if ('' === $num || 'undefined' === $num) {
                    continue;
                }

                // we have a special case with NULL in PostgreSQL, if we allow null
                if ($allowNull && (null === $num || 'null' === $num)) {
                    $qb->andWhere(sprintf('%s IS NULL', $varName));
                    continue;
                }

                if (is_string($num)) {
                    $num = str_replace(',', '.', $num);
                }

                // catch invalid values
                if (!is_numeric($num)) {
                    continue;
                }

                if (is_string($num)) {
                    $num = str_contains($num, '.') ? ((float) $num) : ((int) $num);
                }

                $parameterName = sprintf('%s_%s_%s', str_replace(['.', ' '], '_', $field), $operator, $this->counter++);
                $sign = match ($operator) {
                    'gte' => '>=',
                    'lte' => '<=',
                    'gt' => '>',
                    'lt' => '<',
                    default => '=',
                };

                $qb->andWhere(sprintf('%s %s :%s', $varName, $sign, $parameterName));
                $qb->setParameter($parameterName, $num);
            }
        }
    }

    /**
     * Adds an ->andWhere() statement for boolean search in a QueryBuilder
     *
     * @param QueryBuilder                     $qb
     * @param string                           $field
     * @param string|bool|(string|null)[]|null $value
     * @param bool                             $allowNull
     */
    protected function addBooleanSearchTo(QueryBuilder $qb, string $field, string|bool|array|null $value, bool $allowNull = false): void
    {
        // if nothing was passed or is given falsy, just return
        if (!$allowNull && is_null($value) || is_array($value) && empty($value) || '' === $value || 'undefined' === $value) {
            return;
        }

        if (is_array($value)) {
            $value = reset($value);
        }

        if (!$allowNull && is_null($value) || '' === $value || 'undefined' === $value) {
            return;
        }

        $parameterName = sprintf('%s_%s', str_replace(['.', ' '], '_', $field), $this->counter++);
        $varName = $this->unfoldQueryField($field);

        // allowing to test null
        if ($allowNull && (null === $value || 'null' === $value)) {
            $qb->andWhere(sprintf('%s IS NULL', $varName));

            return;
        }

        // if we got a string
        if (is_string($value) && in_array(strtolower($value), ['false', 'f'])) {
            $value = 0;
        } elseif (is_string($value) && in_array(strtolower($value), ['true', 't'])) {
            $value = 1;

        // try casting it to integer, then to bool -> 2 possibilities left
        } else {
            $value = (bool) (int) $value;
            $value = true === $value ? 1 : 0;
        }

        $qb->andWhere(sprintf('%s = :%s', $varName, $parameterName));
        $qb->setParameter($parameterName, $value);
    }

    /**
     * Adds an ->andWhere() statement for boolean search in a QueryBuilder
     * To be used with an array of ["field_name" => "ASC"|"DESC"|null]
     *
     * @param QueryBuilder          $qb
     * @param array<string, string> $sorts
     */
    protected function addSortsTo(QueryBuilder $qb, array $sorts): void
    {
        foreach ($sorts as $key => $direction) {
            if (!empty($direction)) {
                $cKey = $this->unfoldQueryField($key);
                $qb->addOrderBy($cKey, $direction);
            }
        }
    }

    private function unfoldQueryField(string $field): string
    {
        if (str_starts_with($field, 't.')) {
            $field = substr($field, 2);
        }

        // if only one '.' is used within
        if (false !== ($firstIndex = strpos($field, '.')) && strrpos($field, '.') === $firstIndex) {
            return $field;
        }

        return 't.'.$field;
    }
}
