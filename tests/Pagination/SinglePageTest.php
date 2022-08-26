<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Pagination;

use PHPUnit\Framework\TestCase;
use SkriptManufaktur\SimpleRestBundle\Exception\PaginationException;
use SkriptManufaktur\SimpleRestBundle\Pagination\SinglePage;

class SinglePageTest extends TestCase
{
    public function testSinglePageConstruction(): void
    {
        $items = [4, 5, 6, 7, 8];
        $page = new SinglePage($items, 5, 1, 50);
        $page->boot();

        self::assertSame(1, $page->getCurrentPage());
        self::assertSame(50, $page->getItemCount());
        self::assertSame('string', $page->getItemClass());
        self::assertSame(5, $page->getItemsPerPage());
        self::assertSame(10, $page->getMaxPages());
    }

    public function testRestrictedNext(): void
    {
        self::expectException(PaginationException::class);
        $page = new SinglePage([1, 2], 5, 0, 10);
        $page->nextPage();
    }

    public function testRestrictedPrev(): void
    {
        self::expectException(PaginationException::class);
        $page = new SinglePage([5, 6], 5, 2, 10);
        $page->prevPage();
    }
}
