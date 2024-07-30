<?php

namespace SkriptManufaktur\SimpleRestBundle\Component;

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use RuntimeException;

class ServiceEntityFactory extends ServiceEntityRepository
{
    use DoctrineTransformerTrait;

    public const FILTER_EXACT = 'filter_exact';
    public const FILTER_START = 'filter_start';
    public const FILTER_END = 'filter_end';
    public const FILTER_PARTIAL = 'filter_partial';
    public const FILTER_WORD_START = 'filter_word_start';

    private int $counter = 1;


    public function __construct(ManagerRegistry $registry, string $entityClass)
    {
        parent::__construct($registry, $entityClass);
    }

    /**
     * @return ServiceEntityFactory
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
     * @return ServiceEntityFactory
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
     * @return ServiceEntityFactory
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
     * @return ServiceEntityFactory
     *
     * @throws Exception
     */
    protected function factoryRefresh(object $data): self
    {
        $this->getEntityManager()->refresh($data);

        return $this;
    }

    /**
     * @param object $data
     *
     * @return ServiceEntityFactory
     *
     * @throws Exception
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
     * @return ServiceEntityFactory
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
     * Adds an ->andWhere() statement for string search to a QueryBuilder
     *
     * @param QueryBuilder      $qb
     * @param string            $field
     * @param string|array|null $value
     * @param string            $filterType
     * @param bool              $caseSensitive
     */
    protected function addStringSearchTo(QueryBuilder $qb, string $field, string|array|null $value, string $filterType = self::FILTER_EXACT, bool $caseSensitive = true): void
    {
        // if nothing was passed, just return
        if (empty($value)) {
            return;
        }

        $varName = $this->unfoldQueryField($field);
        $values = is_string($value) ? [$value] : $value;

        if (!$caseSensitive) {
            $varName = sprintf('LOWER(%s)', $varName);
            $values = array_map(fn ($item) => mb_strtolower($item, 'UTF-8'), $values);
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
     * @param QueryBuilder      $qb
     * @param string            $field
     * @param string|array|null $value
     *
     * @throws Exception
     */
    protected function addDateSearchTo(QueryBuilder $qb, string $field, string|array|null $value): void
    {
        // if nothing was passed, just return
        if (empty($value)) {
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
            $value['eq'][] = $value;
        }

        // setup date controls from array
        foreach ($value as $operator => $datesArray) {
            $dates = is_array($datesArray) ? $datesArray : [$datesArray];

            foreach ($dates as $date) {
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
                $qb->setParameter($parameterName, (new DateTime($date))->format($format));
            }
        }
    }

    /**
     * Adds an ->andWhere() statement for boolean search in a QueryBuilder
     *
     * @param QueryBuilder    $qb
     * @param string          $field
     * @param bool|array|null $value
     */
    protected function addBooleanSearchTo(QueryBuilder $qb, string $field, bool|array|null $value): void
    {
        // if nothing was passed or is given falsy, just return
        if (is_null($value) || is_array($value) && empty($value)) {
            return;
        }

        if (is_array($value)) {
            $value = reset($value);
        }

        $parameterName = sprintf('%s_%s', str_replace(['.', ' '], '_', $field), $this->counter++);
        $varName = $this->unfoldQueryField($field);

        // if we got a string
        if (in_array(strtolower($value), ['false', 'f'])) {
            $value = 0;
        } elseif (in_array(strtolower($value), ['true', 't'])) {
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
     * @param QueryBuilder $qb
     * @param array        $sorts
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
