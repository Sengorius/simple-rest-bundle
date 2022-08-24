<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Middleware;

use SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DummyMessage;
use SkriptManufaktur\SimpleRestBundle\Voter\GrantingMiddleware;
use SkriptManufaktur\SimpleRestBundle\Voter\GrantingStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Test\Middleware\MiddlewareTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class GrantingMiddlewareTest extends MiddlewareTestCase
{
    public function testWithoutGranting(): void
    {
        $message = new DummyMessage('Hey');
        $envelope = new Envelope($message);

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->expects($this->never())->method('isGranted');

        $middleware = new GrantingMiddleware($authChecker);
        $envelope = $middleware->handle($envelope, $this->getStackMock());

        static::assertNull($envelope->last(GrantingStamp::class));
    }

    public function testSuccessfulGranting(): void
    {
        $stamp = new GrantingStamp('successful');
        $message = new DummyMessage('Hey');
        $envelope = new Envelope($message, [$stamp]);

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->expects($this->once())
            ->method('isGranted')
                ->with($stamp->getAttribute(), $message)
                ->willReturn(true)
        ;

        $middleware = new GrantingMiddleware($authChecker);
        $envelope = $middleware->handle($envelope, $this->getStackMock());

        static::assertInstanceOf(GrantingStamp::class, $envelope->last(GrantingStamp::class));
    }

    public function testFailedGranting(): void
    {
        static::expectException(AccessDeniedException::class);

        $stamp = new GrantingStamp('failure');
        $message = new DummyMessage('Hey');
        $envelope = new Envelope($message, [$stamp]);

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->expects($this->once())
            ->method('isGranted')
            ->with($stamp->getAttribute(), $message)
            ->willReturn(false)
        ;

        $middleware = new GrantingMiddleware($authChecker);
        $middleware->handle($envelope, $this->getStackMock(false));
    }
}
