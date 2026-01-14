<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use SkriptManufaktur\SimpleRestBundle\Component\ApiBusWrapper;
use SkriptManufaktur\SimpleRestBundle\Exception\ApiBusException;
use SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DummyMessage;
use SkriptManufaktur\SimpleRestBundle\Voter\GrantingStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Stamp\SentStamp;

class ApiBusWrapperTest extends TestCase
{
    public function testDispatchToMessageBus(): void
    {
        $message = new DummyMessage('Hey!');
        $abw = $this->createApiBusWrapper($message);
        $envelope = $abw->dispatch($message);

        static::assertInstanceOf(Envelope::class, $envelope);
        static::assertInstanceOf(DummyMessage::class, $envelope->getMessage());
        static::assertEmpty($envelope->all());
        static::assertSame('Hey!', $envelope->getMessage()->getMessage());
    }

    public function testDispatchToMessageBusWithStamps(): void
    {
        $message = new DummyMessage('Hey!');
        $stamps = [
            new GrantingStamp('grant_1'),
            new GrantingStamp('grant_b'),
        ];
        $abw = $this->createApiBusWrapper($message, $stamps);
        $envelope = $abw->dispatch($message, $stamps);

        static::assertInstanceOf(Envelope::class, $envelope);
        static::assertInstanceOf(DummyMessage::class, $envelope->getMessage());
        static::assertCount(1, $envelope->all());
        static::assertCount(2, $envelope->all(GrantingStamp::class));
        static::assertSame('Hey!', $envelope->getMessage()->getMessage());
    }

    public function testCheckMessageResultBoolean(): void
    {
        $message = new DummyMessage('Hey!');
        $abw = $this->createApiBusWrapper($message);
        $envelope = new Envelope($message, [
            new HandledStamp(true, 'test_handler'),
        ]);
        $result = $abw->checkMessageResult($envelope, [ApiBusWrapper::TYPE_BOOL]);

        static::assertInstanceOf(ApiBusWrapper::class, $abw);
        static::assertIsString(ApiBusWrapper::TYPE_BOOL);
        static::assertSame(true, $result);
    }

    public function testCheckMessageResultArray(): void
    {
        $message = new DummyMessage('Hey!');
        $abw = $this->createApiBusWrapper($message);
        $envelope = new Envelope($message, [
            new HandledStamp([1, 2, 3], 'test_handler'),
        ]);
        $result = $abw->checkMessageResult($envelope, [ApiBusWrapper::TYPE_ARRAY]);

        static::assertInstanceOf(ApiBusWrapper::class, $abw);
        static::assertIsString(ApiBusWrapper::TYPE_ARRAY);
        static::assertSame([1, 2, 3], $result);
    }

    public function testCheckMessageResultInteger(): void
    {
        $message = new DummyMessage('Hey!');
        $abw = $this->createApiBusWrapper($message);
        $envelope = new Envelope($message, [
            new HandledStamp(16, 'test_handler'),
        ]);
        $result = $abw->checkMessageResult($envelope, [ApiBusWrapper::TYPE_INT]);

        static::assertInstanceOf(ApiBusWrapper::class, $abw);
        static::assertIsString(ApiBusWrapper::TYPE_INT);
        static::assertSame(16, $result);
    }

    public function testCheckMessageResultString(): void
    {
        $message = new DummyMessage('Hey!');
        $abw = $this->createApiBusWrapper($message);
        $envelope = new Envelope($message, [
            new HandledStamp('Hello World!', 'test_handler'),
        ]);
        $result = $abw->checkMessageResult($envelope, [ApiBusWrapper::TYPE_STRING]);

        static::assertInstanceOf(ApiBusWrapper::class, $abw);
        static::assertIsString(ApiBusWrapper::TYPE_STRING);
        static::assertSame('Hello World!', $result);
    }

    public function testCheckMessageResultObject(): void
    {
        $message = new DummyMessage('Hey!');
        $abw = $this->createApiBusWrapper($message);
        $envelope = new Envelope($message, [
            new HandledStamp($message, 'test_handler'),
        ]);
        $result = $abw->checkMessageResult($envelope, [DummyMessage::class]);

        static::assertInstanceOf(ApiBusWrapper::class, $abw);
        static::assertIsString(DummyMessage::class);
        static::assertInstanceOf(DummyMessage::class, $result);
        static::assertSame($message, $result);
    }

    public function testCheckMessageResultNull(): void
    {
        $message = new DummyMessage('Hey!');
        $abw = $this->createApiBusWrapper($message);
        $envelope = new Envelope($message, [
            new HandledStamp(null, 'test_handler'),
        ]);
        $result = $abw->checkMessageResult($envelope, [ApiBusWrapper::TYPE_NULL]);

        static::assertInstanceOf(ApiBusWrapper::class, $abw);
        static::assertIsString(ApiBusWrapper::TYPE_NULL);
        static::assertNull($result);
    }

    public function testCheckMessageResultWithoutHandledStamp(): void
    {
        static::expectException(ApiBusException::class);
        static::expectExceptionCode(331);
        static::expectExceptionMessageMatches('/^Message ".+DummyMessage" did not return anything from handler!$/');

        $message = new DummyMessage('Hey!');
        $abw = $this->createApiBusWrapper($message);
        $envelope = new Envelope($message);

        $abw->checkMessageResult($envelope, [ApiBusWrapper::TYPE_NULL]);
    }

    public function testCheckMessageResultWithMultipleHandledStamps(): void
    {
        $message = new DummyMessage('Hey!');
        $abw = $this->createApiBusWrapper($message);
        $envelope = new Envelope($message, [
            new HandledStamp([1, 2, 3], 'test_handler'),
            new HandledStamp([4, 5, 6], 'another_handler'), // <- will be evaluated
        ]);
        $result = $abw->checkMessageResult($envelope, [ApiBusWrapper::TYPE_ARRAY]);

        static::assertInstanceOf(ApiBusWrapper::class, $abw);
        static::assertIsString(ApiBusWrapper::TYPE_ARRAY);
        static::assertSame([4, 5, 6], $result);
    }

    public function testCheckMessageWithMultipleResults(): void
    {
        $message = new DummyMessage('Hey!');
        $abw = $this->createApiBusWrapper($message);
        $envelope = new Envelope($message, [
            new HandledStamp('Hello World!', 'test_handler'),
        ]);
        $result = $abw->checkMessageResult($envelope, [ApiBusWrapper::TYPE_ARRAY, ApiBusWrapper::TYPE_STRING]);

        static::assertInstanceOf(ApiBusWrapper::class, $abw);
        static::assertSame('Hello World!', $result);
    }

    public function testCheckMessageWithUnexpectedResult(): void
    {
        static::expectException(ApiBusException::class);
        static::expectExceptionCode(331);
        static::expectExceptionMessageMatches('/^Message ".+DummyMessage" did not have a stamp with expected value within types \[string, bool\]!$/');

        $message = new DummyMessage('Hey!');
        $abw = $this->createApiBusWrapper($message);
        $envelope = new Envelope($message, [
            new HandledStamp([1, 2, 3], 'test_handler'),
        ]);
        $abw->checkMessageResult($envelope, [ApiBusWrapper::TYPE_STRING, ApiBusWrapper::TYPE_BOOL]);
    }

    public function testCheckMessageWithSentAndReceivedStamps(): void
    {
        $message = new DummyMessage('Hey!');
        $abw = $this->createApiBusWrapper($message);
        $envelope = new Envelope($message, [
            new SentStamp('test_transport'),
            new ReceivedStamp('test_transport'),
            new HandledStamp($message, 'test_handler'),
        ]);
        $result = $abw->checkMessageResult($envelope, [DummyMessage::class]);

        static::assertInstanceOf(ApiBusWrapper::class, $abw);
        static::assertIsString(DummyMessage::class);
        static::assertInstanceOf(DummyMessage::class, $result);
        static::assertSame($message, $result);
    }

    public function testCheckMessageWithSentStampOnly(): void
    {
        $message = new DummyMessage('Hey!');
        $abw = $this->createApiBusWrapper($message);
        $envelope = new Envelope($message, [
            new SentStamp('test_transport'),
        ]);
        $result = $abw->checkMessageResult($envelope, [DummyMessage::class]);

        static::assertInstanceOf(ApiBusWrapper::class, $abw);
        static::assertIsString(DummyMessage::class);
        static::assertFalse($result);
    }

    public function testCheckAllMessageResults(): void
    {
        $message = new DummyMessage('Hey!');
        $stamps = [
            new HandledStamp([1, 2, 3], 'test_handler'),
            new HandledStamp([4, 5, 6], 'another_handler'),
        ];
        $abw = $this->createApiBusWrapper($message, $stamps);
        $envelope = new Envelope($message, $stamps);
        $result = $abw->checkAllMessageResults($envelope, [ApiBusWrapper::TYPE_ARRAY]);

        static::assertInstanceOf(ApiBusWrapper::class, $abw);
        static::assertIsString(ApiBusWrapper::TYPE_ARRAY);
        static::assertSame($stamps, $result);
    }

    public function testCheckAllMessageResultsWithUnexpectedValue(): void
    {
        static::expectException(ApiBusException::class);
        static::expectExceptionCode(331);
        static::expectExceptionMessageMatches('/^Message ".+DummyMessage" did not have a stamp with expected value within types \[array\]!$/');

        $message = new DummyMessage('Hey!');
        $stamps = [
            new HandledStamp([1, 2, 3], 'test_handler'),
            new HandledStamp('asdf', 'another_handler'),
            new HandledStamp([4, 5, 6], 'latest_handler'),
        ];
        $abw = $this->createApiBusWrapper($message, $stamps);
        $envelope = new Envelope($message, $stamps);
        $abw->checkAllMessageResults($envelope, [ApiBusWrapper::TYPE_ARRAY]);
    }

    public function testCheckMessageResultObjectWithProxyClass(): void
    {
        $message = new \Proxies\__CG__\SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DummyMessage('Hey!');
        $abw = $this->createApiBusWrapper($message);
        $envelope = new Envelope($message, [
            new HandledStamp($message, 'test_handler'),
        ]);
        $result = $abw->checkMessageResult($envelope, [DummyMessage::class]);

        static::assertInstanceOf(ApiBusWrapper::class, $abw);
        static::assertInstanceOf(\Proxies\__CG__\SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DummyMessage::class, $result);
        static::assertSame($message, $result);
    }

    public function testCheckMessageResultWithProxyClassNotAllowed(): void
    {
        static::expectException(ApiBusException::class);
        static::expectExceptionCode(331);
        static::expectExceptionMessage(
            'Message "Proxies\__CG__\SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DummyMessage" did not '
            .'have a stamp with expected value within types [SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DummyMessage]!'
        );

        $message = new \Proxies\__CG__\SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DummyMessage('Hey!');
        $abw = $this->createApiBusWrapper($message);
        $envelope = new Envelope($message, [
            new HandledStamp($message, 'test_handler'),
        ]);
        $abw->checkMessageResult($envelope, [DummyMessage::class], false);
    }

    private function createApiBusWrapper(object $message, array $stamps = []): ApiBusWrapper
    {
        $envelope = Envelope::wrap($message, $stamps);
        $messageBus = $this->createStub(MessageBusInterface::class);
        $messageBus->method('dispatch')
            ->with($message, $stamps)
            ->willReturn($envelope)
        ;

        return new ApiBusWrapper($messageBus);
    }
}
