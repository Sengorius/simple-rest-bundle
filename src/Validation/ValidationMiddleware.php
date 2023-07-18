<?php

namespace SkriptManufaktur\SimpleRestBundle\Validation;

use SkriptManufaktur\SimpleRestBundle\Exception\ValidationException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ValidationStamp;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidationMiddleware implements MiddlewareInterface
{
    private ValidatorInterface $validator;


    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        $groups = null;

        /** @var ValidationStamp|null $validationStamp */
        if ($validationStamp = $envelope->last(ValidationStamp::class)) {
            $groups = $validationStamp->getGroups();
        }

        $violations = $this->validator->validate($message, null, $groups);

        if (count($violations)) {
            throw new ValidationException($message, $violations);
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
