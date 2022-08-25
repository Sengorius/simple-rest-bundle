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
        static::assertNull($envelope->last(AfterHandleGrantingStamp::class));
    }

    public function testSuccessfulGranting(): void
    {
        $stamp = new GrantingStamp('successful');
        $envelope = new Envelope(new DummyMessage('Hey'), [$stamp]);

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->expects($this->once())
            ->method('isGranted')
                ->with($stamp->getAttribute(), $envelope->withoutAll(GrantingStamp::class))
                ->willReturn(true)
        ;

        $middleware = new GrantingMiddleware($authChecker);
        $finalEnvelope = $middleware->handle($envelope, $this->getStackMock());
        $finalStamp = $finalEnvelope->last(GrantingStamp::class);

        static::assertInstanceOf(GrantingStamp::class, $finalStamp);
        static::assertSame('successful', $finalStamp->getAttribute());
        static::assertTrue($finalStamp->getVote());
    }

    public function testFailedGranting(): void
    {
        static::expectException(AccessDeniedException::class);

        $stamp = new GrantingStamp('failure');
        $envelope = new Envelope(new DummyMessage('Hey'), [$stamp]);

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->expects($this->once())
            ->method('isGranted')
                ->with($stamp->getAttribute(), $envelope->withoutAll(GrantingStamp::class))
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
                ->with($stamp->getAttribute(), $envelope->withoutAll(AfterHandleGrantingStamp::class))
                ->willReturn(true)
        ;

        $middleware = new GrantingMiddleware($authChecker);
        $finalEnvelope = $middleware->handle($envelope, $this->getStackMock());
        $finalStamp = $finalEnvelope->last(AfterHandleGrantingStamp::class);

        static::assertInstanceOf(AfterHandleGrantingStamp::class, $finalStamp);
        static::assertSame('successful', $finalStamp->getAttribute());
        static::assertTrue($finalStamp->getVote());
    }

    public function testFailedGrantingAfterwards(): void
    {
        static::expectException(AccessDeniedException::class);

        $stamp = new AfterHandleGrantingStamp('failure');
        $envelope = new Envelope(new DummyMessage('Hey'), [$stamp]);

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->expects($this->once())
            ->method('isGranted')
                ->with($stamp->getAttribute(), $envelope->withoutAll(AfterHandleGrantingStamp::class))
                ->willReturn(false)
        ;

        $middleware = new GrantingMiddleware($authChecker);
        $middleware->handle($envelope, $this->getStackMock());
    }

    public function testComplexStampHandling(): void
    {
        $stamp1 = new GrantingStamp('test_1');
        $stamp2 = new GrantingStamp('test_2');
        $stamp3 = new AfterHandleGrantingStamp('test_3');
        $envelope = new Envelope(new DummyMessage('Hey'), [$stamp1, $stamp2, $stamp3]);

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->expects($this->atLeast(3))
            ->method('isGranted')
            ->willReturnCallback(fn (string $attr, Envelope $env) => 'test_3' !== $attr)
        ;

        static::assertNull($stamp1->getVote());
        static::assertNull($stamp2->getVote());
        static::assertNull($stamp3->getVote());

        $middleware = new GrantingMiddleware($authChecker, false);
        $finalEnvelope = $middleware->handle($envelope, $this->getStackMock());
        $finalAfterStamp = $finalEnvelope->last(AfterHandleGrantingStamp::class);

        static::assertInstanceOf(AfterHandleGrantingStamp::class, $finalAfterStamp);
        static::assertSame('test_3', $finalAfterStamp->getAttribute());
        static::assertFalse($finalAfterStamp->getVote());

        $grantingStamps = $finalEnvelope->all(GrantingStamp::class);
        $firstStamp = $grantingStamps[0];
        $secondStamp = $grantingStamps[1];

        static::assertCount(2, $grantingStamps);
        static::assertInstanceOf(GrantingStamp::class, $firstStamp);
        static::assertInstanceOf(GrantingStamp::class, $secondStamp);
        static::assertSame('test_1', $firstStamp->getAttribute());
        static::assertSame('test_2', $secondStamp->getAttribute());
        static::assertTrue($firstStamp->getVote());
        static::assertTrue($secondStamp->getVote());
    }
}
