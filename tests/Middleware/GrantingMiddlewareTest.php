<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Middleware;

use SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DummyMessage;
use SkriptManufaktur\SimpleRestBundle\Voter\AfterHandleGrantingStamp;
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
        $envelope = new Envelope(new DummyMessage('Hey'), [$stamp]);

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->expects($this->once())
            ->method('isGranted')
                ->with($stamp->getAttribute(), $envelope)
                ->willReturn(true)
        ;

        $middleware = new GrantingMiddleware($authChecker);
        $finalEnvelope = $middleware->handle($envelope, $this->getStackMock());
        $finalStamp = $finalEnvelope->last(GrantingStamp::class);

        static::assertInstanceOf(GrantingStamp::class, $finalStamp);
        static::assertSame('successful', $finalStamp->getAttribute());
    }

    public function testFailedGranting(): void
    {
        static::expectException(AccessDeniedException::class);

        $stamp = new GrantingStamp('failure');
        $envelope = new Envelope(new DummyMessage('Hey'), [$stamp]);

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->expects($this->once())
            ->method('isGranted')
            ->with($stamp->getAttribute(), $envelope)
            ->willReturn(false)
        ;

        $middleware = new GrantingMiddleware($authChecker);
        $middleware->handle($envelope, $this->getStackMock(false));
    }

    public function testSuccessfulGrantingAfterwards(): void
    {
        $stamp = new AfterHandleGrantingStamp('successful');
        $envelope = new Envelope(new DummyMessage('Hey'), [$stamp]);

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->expects($this->once())
            ->method('isGranted')
            ->with($stamp->getAttribute(), $envelope)
            ->willReturn(true)
        ;

        $middleware = new GrantingMiddleware($authChecker);
        $finalEnvelope = $middleware->handle($envelope, $this->getStackMock());
        $finalStamp = $finalEnvelope->last(AfterHandleGrantingStamp::class);

        static::assertInstanceOf(AfterHandleGrantingStamp::class, $finalStamp);
        static::assertSame('successful', $finalStamp->getAttribute());
    }

    public function testFailedGrantingAfterwards(): void
    {
        static::expectException(AccessDeniedException::class);

        $stamp = new AfterHandleGrantingStamp('failure');
        $envelope = new Envelope(new DummyMessage('Hey'), [$stamp]);

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->expects($this->once())
            ->method('isGranted')
            ->with($stamp->getAttribute(), $envelope)
            ->willReturn(false)
        ;

        $middleware = new GrantingMiddleware($authChecker);
        $middleware->handle($envelope, $this->getStackMock());
    }
}
