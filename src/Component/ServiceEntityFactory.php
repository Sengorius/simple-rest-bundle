<?php

namespace SkriptManufaktur\SimpleRestBundle\Component;

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use RuntimeException;
use function get_class;

/**
 * Class ServiceEntityFactory
 */
class ServiceEntityFactory extends ServiceEntityRepository
{
    const FILTER_EXACT = 'filter_exact';
    const FILTER_START = 'filter_start';
    const FILTER_END = 'filter_end';
    const FILTER_PARTIAL = 'filter_partial';
    const FILTER_WORD_START = 'filter_word_start';

    private int $counter = 1;


    /**
     * ServiceEntityFactory constructor.
     *
     * @param ManagerRegistry $registry
     * @param string          $entityClass
     */
    public function __construct(ManagerRegistry $registry, string $entityClass = '')
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

        if (!$em->contains($data) || $this->isDeferredExplicit($data)) {
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
     * Checks if doctrine does not manage data automatically.
     *
     * @param object $data
     *
     * @return bool
     */
    protected function isDeferredExplicit(object $data): bool
    {
        $realClassName = $this->getRealClassName(get_class($data));
        $classMetadata = $this->getEntityManager()->getClassMetadata($realClassName);

        if ($classMetadata instanceof ClassMetadataInfo && method_exists($classMetadata, 'isChangeTrackingDeferredExplicit')) {
            return $classMetadata->isChangeTrackingDeferredExplicit();
        }

        return false;
    }

    /**
     * Get the real class name of a class name that could be a proxy.
     *
     * @param string $className
     *
     * @return string
     */
    protected function getRealClassName(string $className): string
    {
        $positionCg = strrpos($className, '\\__CG__\\');
        $positionPm = strrpos($className, '\\__PM__\\');

        // __CG__: Doctrine Common Marker for Proxy (ODM < 2.0 and ORM < 3.0)
        // __PM__: Ocramius Proxy Manager (ODM >= 2.0)
        if (false === $positionCg && false === $positionPm) {
            return $className;
        }

        if (false !== $positionCg) {
            return substr($className, $positionCg + 8);
        }

        $className = ltrim($className, '\\');

        return substr(
            $className,
            8 + $positionPm,
            strrpos($className, '\\') - ($positionPm + 8)
        );
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
    protected function addStringSearchTo(QueryBuilder $qb, string $field, $value, string $filterType = self::FILTER_EXACT, bool $caseSensitive = true): void
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

            switch ($filterType) {
                case self::FILTER_EXACT:
                    $statement = sprintf('%s LIKE :%s', $varName, $parameterName);
                    break;

                case self::FILTER_PARTIAL:
                    $statement = sprintf('%s LIKE CONCAT(\'%%\', :%s, \'%%\')', $varName, $parameterName);
                    break;

                case self::FILTER_START:
                    $statement = sprintf('%s LIKE CONCAT(:%s, \'%%\')', $varName, $parameterName);
                    break;

                case self::FILTER_END:
                    $statement = sprintf('%s LIKE CONCAT(\'%%\', :%s)', $varName, $parameterName);
                    break;

                case self::FILTER_WORD_START:
                    $statement = sprintf('%1$s LIKE CONCAT(:%2$s, \'%%\') OR %1$s LIKE CONCAT(\'%% \', :%2$s, \'%%\')', $varName, $parameterName);
                    break;

                default:
                    throw new RuntimeException(sprintf('Filter-Type "%s" is unknown!', $filterType));
            }

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
    protected function addDateSearchTo(QueryBuilder $qb, string $field, $value): void
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

                switch ($operator) {
                    case 'gte':     $sign = '>=';   break;
                    case 'lte':     $sign = '<=';   break;
                    case 'gt':      $sign = '>';    break;
                    case 'lt':      $sign = '<';    break;
                    default:        $sign = '=';    break;
                }

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
    protected function addBooleanSearchTo(QueryBuilder $qb, string $field, $value): void
    {
        // if nothing was passed or is given falsy, just return
        if (empty($value)) {
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

    /**
     * @param string $field
     *
     * @return string
     */
    private function unfoldQueryField(string $field): string
    {
        if (0 === strpos($field, 't.')) {
            $field = substr($field, 2);
        }

        // if only one '.' is used within
        if (false !== ($firstIndex = strpos($field, '.')) && $firstIndex === strrpos($field, '.')) {
            return $field;
        }

        return 't.'.$field;
    }
}
