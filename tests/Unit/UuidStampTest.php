<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SkriptManufaktur\SimpleRestBundle\MessageTagging\UuidStamp;
use Symfony\Component\Uid\UuidV7;

class UuidStampTest extends TestCase
{
    public function testCreationEmpty(): void
    {
        $stamp = new UuidStamp();

        static::assertInstanceOf(UuidStamp::class, $stamp);
        static::assertInstanceOf(UuidV7::class, $stamp->getUuid());
    }

    public function testCreationWithUuid(): void
    {
        $uuid = new UuidV7();
        $stamp = new UuidStamp($uuid);

        static::assertInstanceOf(UuidStamp::class, $stamp);
        static::assertInstanceOf(UuidV7::class, $stamp->getUuid());
        static::assertSame($uuid, $stamp->getUuid());
    }
}
