<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Middleware;

use SkriptManufaktur\SimpleRestBundle\Exception\ValidationException;
use SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DummyEntity;
use SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\EmbeddedDummyEntity;
use SkriptManufaktur\SimpleRestBundle\Validation\ValidationMiddleware;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Test\Middleware\MiddlewareTestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidationMiddlewareTest extends MiddlewareTestCase
{
    public function testSuccessfulValidation(): void
    {
        $message = new DummyEntity();
        $message->setId(96);
        $message->setUsername('John');
        $message->setEmail('john@example.com');
        $message->addEmbed(
            (new EmbeddedDummyEntity())
                ->setId(17)
                ->setType('t1')
        );

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
                ->with($message, null, null)
                ->willReturn(new ConstraintViolationList())
        ;

        $middleware = new ValidationMiddleware($validator);
        $envelope = $middleware->handle(new Envelope($message), $this->getStackMock());

        static::assertInstanceOf(Envelope::class, $envelope);
    }

    public function testMainMessageFailedValidation(): void
    {
        $message = new DummyEntity();
        $message->setId(96);
        $message->setUsername('Ti');
        $message->setEmail('example.com');

        $violations = new ConstraintViolationList([
            new ConstraintViolation('Username is to short', '', [], null, 'username', null),
            new ConstraintViolation('Invalid e-mail address', '', [], null, 'email', null),
        ]);

        $expectedViolations = [
            'root' => [],
            'username' => [
                'Username is to short',
            ],
            'email' => [
                'Invalid e-mail address',
            ],
        ];

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
                ->with($message, null, null)
                ->willReturn($violations)
        ;

        try {
            $middleware = new ValidationMiddleware($validator);
            $middleware->handle(new Envelope($message), $this->getStackMock(false));
        } catch (ValidationException $exception) {
            static::assertInstanceOf(ConstraintViolationList::class, $exception->getViolations());
            static::assertSame($expectedViolations, $exception->getStringifiedViolations());
        }
    }
}
