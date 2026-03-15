<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Unit;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use SkriptManufaktur\SimpleRestBundle\MessageTagging\UuidContextStack;
use Symfony\Component\Uid\UuidV7;

class UuidContextStackTest extends TestCase
{
    public function testPushToContext(): array
    {
        $uuid1 = new UuidV7();
        $uuid2 = new UuidV7();
        $context = new UuidContextStack();

        static::assertSame(1, $context->push($uuid1));
        static::assertCount(1, $context->head());

        static::assertSame(2, $context->push($uuid2));
        static::assertCount(2, $context->head());

        return [$context, $uuid1];
    }

    #[Depends('testPushToContext')]
    public function testPopFromContext(array $params): void
    {
        [$context, $uuid1] = $params;
        static::assertInstanceOf(UuidContextStack::class, $context);
        static::assertInstanceOf(UuidV7::class, $uuid1);

        static::assertSame(1, $context->pop());
        static::assertCount(1, $context->head());

        [$innerUuid] = $context->head();

        static::assertInstanceOf(UuidV7::class, $innerUuid);
        static::assertSame($uuid1, $innerUuid);

        static::assertSame(0, $context->pop());
        static::assertEmpty($context->head());
    }
}
