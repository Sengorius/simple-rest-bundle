<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Unit;

use Doctrine\ORM\NonUniqueResultException;
use LogicException;
use PHPUnit\Framework\TestCase;
use SkriptManufaktur\SimpleRestBundle\Component\ApiResponse;
use SkriptManufaktur\SimpleRestBundle\Exception\ApiNotFoundException;
use SkriptManufaktur\SimpleRestBundle\Exception\ApiProcessException;
use SkriptManufaktur\SimpleRestBundle\Exception\ValidationException;
use SkriptManufaktur\SimpleRestBundle\Listener\ApiResponseListener;
use SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DummyEntity;
use SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DummyMessage;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class ApiResponseListenerTest extends TestCase
{
    public static HttpKernel $kernel;
    public static ApiResponseListener $listener;


    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$kernel = new HttpKernel(new EventDispatcher(), new ControllerResolver());
        self::$listener = new ApiResponseListener(['api']);
    }

    public function testApiResponseType(): void
    {
        $event = new ResponseEvent(
            self::$kernel,
            $this->createRequest(),
            HttpKernelInterface::MAIN_REQUEST,
            ApiResponse::create()
        );

        // nothing should happen
        self::$listener->testApiResponseType($event);

        static::assertTrue(true);
    }

    public function testInvalidApiResponseType(): void
    {
        $event = new ResponseEvent(
            self::$kernel,
            $this->createRequest(),
            HttpKernelInterface::MAIN_REQUEST,
            new \Symfony\Component\HttpFoundation\Response()
        );

        static::expectException(LogicException::class);
        static::expectExceptionMessageMatches('/^Response has to be an instance of ApiResponse/');

        self::$listener->testApiResponseType($event);
    }

    public function testFlashbagInjection(): void
    {
        if (!interface_exists(SessionInterface::class)) {
            static::markTestSkipped('No Session installed, skipping test.');
        }

        $response = ApiResponse::create();
        $request = $this->createRequest(true);
        $flashBag = $request->getSession()->getFlashBag();
        $flashBag->add('success', 'Yay, it worked!');
        $flashBag->add('info', 'Something happened.');
        $flashBag->add('info', 'Another info for you.');

        $expectedMessages = [
            'success' => [
                'Yay, it worked!',
            ],
            'info' => [
                'Something happened.',
                'Another info for you.',
            ],
            'warning' => [],
            'error' => [],
        ];

        $event = new ResponseEvent(
            self::$kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        self::$listener->addFlashbagMessages($event);

        static::assertInstanceOf(ApiResponse::class, $event->getResponse());
        static::assertNotEmpty($response->getMessages());
        static::assertCount(1, $response->getMessages()['success']);
        static::assertCount(2, $response->getMessages()['info']);
        static::assertSame($expectedMessages, $response->getMessages());
    }

    public function testHandlerFailedExceptionFormatting(): void
    {
        $event = new ExceptionEvent(
            self::$kernel,
            $this->createRequest(),
            HttpKernelInterface::MAIN_REQUEST,
            new HandlerFailedException(
                new Envelope(new DummyMessage('Hello World!')),
                [new LogicException('This is my error!')]
            )
        );

        self::$listener->formatException($event);

        $response = $event->getResponse();

        static::assertInstanceOf(ApiResponse::class, $response);
        static::assertInstanceOf(LogicException::class, $response->getThrowable());
        static::assertSame('This is my error!', $response->getData()['message']);
        static::assertSame(500, $response->getStatusCode());
    }

    public function testAccessDeniedExceptionFormatting(): void
    {
        $event = new ExceptionEvent(
            self::$kernel,
            $this->createRequest(),
            HttpKernelInterface::MAIN_REQUEST,
            new AccessDeniedHttpException('This is my error!')
        );

        self::$listener->formatException($event);

        $response = $event->getResponse();

        static::assertInstanceOf(ApiResponse::class, $response);
        static::assertInstanceOf(AccessDeniedHttpException::class, $response->getThrowable());
        static::assertSame('This is my error!', $response->getData()['message']);
        static::assertSame(403, $response->getStatusCode());
    }

    public function testValidationExceptionFormatting(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('Username is to short', '', [], null, 'username', null),
            new ConstraintViolation('Invalid e-mail address', '', [], null, 'email', null),
        ]);
        $expectedValidation = '{"username":["Username is to short"],"email":["Invalid e-mail address"]}';

        $event = new ExceptionEvent(
            self::$kernel,
            $this->createRequest(),
            HttpKernelInterface::MAIN_REQUEST,
            new ValidationException(new DummyEntity(), $violations)
        );

        self::$listener->formatException($event);

        $response = $event->getResponse();

        static::assertInstanceOf(ApiResponse::class, $response);
        static::assertNull($response->getThrowable());
        static::assertEmpty($response->getData());
        static::assertSame(422, $response->getStatusCode());
        static::assertSame(
            sprintf(
                '{"data":[],"messages":{"success":[],"info":[],"warning":[],"error":[]},"validation":%s}',
                $expectedValidation
            ),
            $response->getContent()
        );
    }

    public function testNoUniqueResultExceptionFormatting(): void
    {
        $event = new ExceptionEvent(
            self::$kernel,
            $this->createRequest(),
            HttpKernelInterface::MAIN_REQUEST,
            new NonUniqueResultException('This is my error!')
        );

        self::$listener->formatException($event);

        $response = $event->getResponse();

        static::assertInstanceOf(ApiResponse::class, $response);
        static::assertInstanceOf(NonUniqueResultException::class, $response->getThrowable());
        static::assertSame('This is my error!', $response->getData()['message']);
        static::assertSame(404, $response->getStatusCode());
    }

    public function testNotFoundHttpExceptionFormatting(): void
    {
        $event = new ExceptionEvent(
            self::$kernel,
            $this->createRequest(),
            HttpKernelInterface::MAIN_REQUEST,
            new NotFoundHttpException('This is my error!')
        );

        self::$listener->formatException($event);

        $response = $event->getResponse();

        static::assertInstanceOf(ApiResponse::class, $response);
        static::assertInstanceOf(NotFoundHttpException::class, $response->getThrowable());
        static::assertSame('This is my error!', $response->getData()['message']);
        static::assertSame(404, $response->getStatusCode());
    }

    public function testApiNotFoundExceptionFormatting(): void
    {
        $event = new ExceptionEvent(
            self::$kernel,
            $this->createRequest(),
            HttpKernelInterface::MAIN_REQUEST,
            new ApiNotFoundException('This is my error!')
        );

        self::$listener->formatException($event);

        $response = $event->getResponse();

        static::assertInstanceOf(ApiResponse::class, $response);
        static::assertInstanceOf(ApiNotFoundException::class, $response->getThrowable());
        static::assertSame('This is my error!', $response->getData()['message']);
        static::assertSame(404, $response->getStatusCode());
    }

    public function testApiProcessExceptionFormatting(): void
    {
        $event = new ExceptionEvent(
            self::$kernel,
            $this->createRequest(),
            HttpKernelInterface::MAIN_REQUEST,
            new ApiProcessException('This is my error!')
        );

        self::$listener->formatException($event);

        $response = $event->getResponse();

        static::assertInstanceOf(ApiResponse::class, $response);
        static::assertInstanceOf(ApiProcessException::class, $response->getThrowable());
        static::assertSame('This is my error!', $response->getData()['message']);
        static::assertSame(400, $response->getStatusCode());
    }

    private function createRequest(bool $withSession = false): Request
    {
        $request = new Request(attributes: [
            '_firewall_context' => 'api',
        ]);

        if ($withSession) {
            $request->setSession(new Session(new MockFileSessionStorage()));
        }

        return $request;
    }
}
