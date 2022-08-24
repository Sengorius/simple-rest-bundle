<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SkriptManufaktur\SimpleRestBundle\Component\ApiResponse;

class ApiResponseTest extends TestCase
{
    public function testCreation(): void
    {
        $response = new ApiResponse();

        static::assertInstanceOf(ApiResponse::class, $response);
        static::assertSame(200, $response->getStatusCode());
        static::assertSame([], $response->getData());
        static::assertSame(
            '{"data":[],"messages":{"success":[],"info":[],"warning":[],"error":[]},"validation":[]}',
            $response->getContent()
        );
    }

    public function testStaticCreation(): void
    {
        $response = ApiResponse::create();

        static::assertInstanceOf(ApiResponse::class, $response);
        static::assertSame(200, $response->getStatusCode());
        static::assertSame([], $response->getData());
        static::assertSame(
            '{"data":[],"messages":{"success":[],"info":[],"warning":[],"error":[]},"validation":[]}',
            $response->getContent()
        );
    }

    public function testContentOutput(): void
    {
        $response = ApiResponse::create(['data' => 'test'], 203);
        $response->setData($response->getData() + ['output' => 1234]);

        static::assertInstanceOf(ApiResponse::class, $response);
        static::assertSame(203, $response->getStatusCode());
        static::assertSame(['data' => 'test', 'output' => 1234], $response->getData());
        static::assertSame(
            '{"data":{"data":"test","output":1234},"messages":{"success":[],"info":[],"warning":[],"error":[]},"validation":[]}',
            $response->getContent()
        );
    }

    public function testMessages(): void
    {
        $response = ApiResponse::create();
        $response->addMessage(ApiResponse::MSGTYPE_SUCCESS, 'yes');
        $response->addMessage(ApiResponse::MSGTYPE_ERROR, 'no');

        static::assertInstanceOf(ApiResponse::class, $response);
        static::assertSame(200, $response->getStatusCode());
        static::assertSame([], $response->getData());
        static::assertSame(
            '{"data":[],"messages":{"success":["yes"],"info":[],"warning":[],"error":["no"]},"validation":[]}',
            $response->getContent()
        );
    }

    public function testValidation(): void
    {
        $validation = '{"root":["Problem in root"],"name":["Problem with name"],"address":["Prob. with a","Prob. with b"]}';
        $response = ApiResponse::create([], 400)
            ->addValidationIssue('', 'Problem in root')
            ->addValidationIssue('name', 'Problem with name')
            ->addValidationIssue('address', 'Prob. with a')
            ->addValidationIssue('address', 'Prob. with b')
        ;

        static::assertInstanceOf(ApiResponse::class, $response);
        static::assertSame(400, $response->getStatusCode());
        static::assertSame([], $response->getData());
        static::assertSame(
            sprintf('{"data":[],"messages":{"success":[],"info":[],"warning":[],"error":[]},"validation":%s}', $validation),
            $response->getContent()
        );
    }

    public function testThrowable(): void
    {
        $data = '{"message":"This is an error!","status":0,"code":5}';
        $exception = new RuntimeException('This is an error!', 5);
        $response = ApiResponse::create(['test' => 'test'], 500)->setThrowable($exception);

        static::assertInstanceOf(ApiResponse::class, $response);
        static::assertSame(500, $response->getStatusCode());
        static::assertSame(['message' => 'This is an error!', 'status' => 0, 'code' => 5], $response->getData());
        static::assertSame(
            sprintf('{"data":%s,"messages":{"success":[],"info":[],"warning":[],"error":[]},"validation":[]}', $data),
            $response->getContent()
        );
    }
}
