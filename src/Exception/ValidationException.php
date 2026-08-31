<?php

namespace SkriptManufaktur\SimpleRestBundle\Exception;

use RuntimeException;
use SkriptManufaktur\SimpleRestBundle\Validation\ValidationPreparationTrait;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Throwable;
use function get_class;

class ValidationException extends RuntimeException
{
    use ValidationPreparationTrait;

    public const string VALIDATION_ROOT_KEY = 'root';
    public const int EXCEPTION_CODE = 334;

    private object $entity;
    private ConstraintViolationListInterface $violations;


    public function __construct(object $entity, ConstraintViolationListInterface $violations, Throwable|null $previous = null)
    {
        parent::__construct(
            self::buildMessage($entity, $violations),
            self::EXCEPTION_CODE,
            $previous
        );

        $this->violations = $violations;
        $this->entity = $entity;
    }

    public static function fromSingle(ConstraintViolationInterface $violation): self
    {
        return new self($violation->getRoot(), new ConstraintViolationList([$violation]));
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

    /** @return array<string, string[]> */
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

    /**
     * Builds the exception message, containing every violation, to make the actual
     * cause visible in logs, where only the message is written
     *
     * @param object                           $entity
     * @param ConstraintViolationListInterface $violations
     *
     * @return string
     */
    private static function buildMessage(object $entity, ConstraintViolationListInterface $violations): string
    {
        $message = sprintf('Validation for object "%s" has failed!', get_class($entity));
        $stringified = [];

        /** @var ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $violationMessage = trim((string) $violation->getMessage());

            if ('' === $violationMessage) {
                continue;
            }

            $propertyPath = trim($violation->getPropertyPath());
            $stringified[] = sprintf(
                '%s: %s',
                '' !== $propertyPath ? $propertyPath : self::VALIDATION_ROOT_KEY,
                $violationMessage
            );
        }

        if (empty($stringified)) {
            return $message;
        }

        return sprintf('%s Violations: %s', $message, implode('; ', $stringified));
    }
}
