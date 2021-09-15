<?php

namespace SkriptManufaktur\SimpleRestBundle\Exception;

use RuntimeException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Throwable;
use function get_class;
use function is_object;

/**
 * Class ValidationException
 */
class ValidationException extends RuntimeException
{
    const VALIDATION_ROOT_KEY = 'root';
    const EXCEPTION_CODE = 334;

    private object $entity;
    private ConstraintViolationListInterface $violations;


    /**
     * ValidationException constructor.
     *
     * @param object                           $entity
     * @param ConstraintViolationListInterface $violations
     * @param Throwable|null                   $previous
     */
    public function __construct(object $entity, ConstraintViolationListInterface $violations, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Validierung für Objekt "%s" fehlgeschlagen!', get_class($entity)), self::EXCEPTION_CODE, $previous);

        $this->violations = $violations;
        $this->entity = $entity;
    }

    public function getEntity(): object
    {
        return $this->entity;
    }

    public function getEntityClass(): string
    {
        return is_object($this->entity) ? get_class($this->entity) : '';
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }

    public function getStringifiedViolations(): array
    {
        $violations = [
            self::VALIDATION_ROOT_KEY => [],
        ];

        /** @var ConstraintViolationInterface $violation */
        foreach ($this->violations as $violation) {
            $propertyPath = $violation->getPropertyPath();

            if ('' === $propertyPath) {
                $propertyPath = self::VALIDATION_ROOT_KEY;
            }

            if (!isset($violations[$propertyPath])) {
                $violations[$propertyPath] = [];
            }

            $violations[$propertyPath][] = $violation->getMessage();
        }

        return $violations;
    }
}
