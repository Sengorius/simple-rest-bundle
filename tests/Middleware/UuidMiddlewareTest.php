<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Middleware;

use SkriptManufaktur\SimpleRestBundle\MessageTagging\UuidContextStack;
use SkriptManufaktur\SimpleRestBundle\MessageTagging\UuidMiddleware;
use SkriptManufaktur\SimpleRestBundle\MessageTagging\UuidStamp;
use SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DummyMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Test\Middleware\MiddlewareTestCase;
use Symfony\Component\Uid\UuidV7;

class UuidMiddlewareTest extends MiddlewareTestCase
{
    public function testWithEmptyContext(): void
    {
        $message = new DummyMessage('Hey');
        $envelope = new Envelope($message);

        $context = new UuidContextStack();
        $middleware = new UuidMiddleware($context);

        static::assertEmpty($context->head());

        $envelope = $middleware->handle($envelope, $this->getStackMock());
        $stamp = $envelope->last(UuidStamp::class);

        static::assertInstanceOf(UuidStamp::class, $stamp);
        static::assertInstanceOf(UuidV7::class, $stamp->getUuid());

        // stack should be empty again, after handling
        static::assertEmpty($context->head());
    }
}
