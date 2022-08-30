<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SkriptManufaktur\SimpleRestBundle\Voter\AfterHandleGrantingStamp;
use SkriptManufaktur\SimpleRestBundle\Voter\GrantingStamp;
use SkriptManufaktur\SimpleRestBundle\Voter\GrantingStampInterface;

class GrantingStampTest extends TestCase
{
    public function testCreation(): void
    {
        $stamp = new GrantingStamp('access');

        static::assertInstanceOf(GrantingStampInterface::class, $stamp);
        static::assertInstanceOf(GrantingStamp::class, $stamp);
        static::assertSame('access', $stamp->getAttribute());
        static::assertNull($stamp->getVote());
    }

    public function testCreationWithVote(): void
    {
        $stamp = new GrantingStamp('access', true);

        static::assertInstanceOf(GrantingStampInterface::class, $stamp);
        static::assertInstanceOf(GrantingStamp::class, $stamp);
        static::assertSame('access', $stamp->getAttribute());
        static::assertTrue($stamp->getVote());
    }

    public function testAfterHandleCreation(): void
    {
        $stamp = new AfterHandleGrantingStamp('access');

        static::assertInstanceOf(GrantingStampInterface::class, $stamp);
        static::assertInstanceOf(AfterHandleGrantingStamp::class, $stamp);
        static::assertSame('access', $stamp->getAttribute());
        static::assertNull($stamp->getVote());
    }
}
