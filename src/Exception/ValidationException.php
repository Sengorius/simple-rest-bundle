<?php

namespace SkriptManufaktur\SimpleRestBundle\Exception;

use RuntimeException;
use SkriptManufaktur\SimpleRestBundle\Validation\ValidationPreparationTrait;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Throwable;
use function get_class;

class ValidationException extends RuntimeException
{
    use ValidationPreparationTrait;

    public const VALIDATION_ROOT_KEY = 'root';
    public const EXCEPTION_CODE = 334;

    private object $entity;
    private ConstraintViolationListInterface $violations;


    public function __construct(object $entity, ConstraintViolationListInterface $violations, Throwable|null $previous = null)
    {
        parent::__construct(
            sprintf('Validation for object "%s" has failed!', get_class($entity)),
            self::EXCEPTION_CODE,
            $previous
        );

        $this->violations = $violations;
        $this->entity = $entity;
    }

    public function getEntity(): object
    {
        return $this->entity;
    }

    public function getEntityClass(): string
    {
        return get_class($this->entity);
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
            if (empty($violation->getMessage())) {
                continue;
            }

            $propertyPath = $this->prepareValidation(
                propertyPath: $violation->getPropertyPath(),
                validationList: $violations,
                defaultRoot: self::VALIDATION_ROOT_KEY
            );

            $violations[$propertyPath][] = $violation->getMessage();
        }

        return $violations;
    }
}
