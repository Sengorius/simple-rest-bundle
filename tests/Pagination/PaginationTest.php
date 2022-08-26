<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Pagination;

use PHPUnit\Framework\TestCase;
use SkriptManufaktur\SimpleRestBundle\Exception\PaginationException;
use SkriptManufaktur\SimpleRestBundle\Pagination\Pagination;

class PaginationTest extends TestCase
{
    public function testPaginationContruction(): void
    {
        $items = [1, 2, 3, 4, 5];
        $pagination = new Pagination($items, 3);
        $pagination->boot();

        self::assertSame(0, $pagination->getCurrentPage());
        self::assertSame(5, $pagination->getItemCount());
        self::assertSame('string', $pagination->getItemClass());
        self::assertSame(3, $pagination->getItemsPerPage());
        self::assertSame(2, $pagination->getMaxPages());
    }

    public function testFailedBooting(): void
    {
        self::expectException(PaginationException::class);

        $pagination = new Pagination([1, 2, 3], 2);
        self::assertSame(null, $pagination->getMaxPages());
    }

    public function testMaxPages(): void
    {
        $items = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17];

        $pagination = (new Pagination($items, 3))->boot();
        self::assertSame(6, $pagination->getMaxPages());

        $pagination = (new Pagination($items, 4))->boot();
        self::assertSame(5, $pagination->getMaxPages());

        $pagination = (new Pagination($items, 2))->boot();
        self::assertSame(9, $pagination->getMaxPages());

        $pagination = (new Pagination($items, 8))->boot();
        self::assertSame(3, $pagination->getMaxPages());
    }

    public function testNextPages(): void
    {
        $perPage = 4;
        $items = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17];
        $pagination = (new Pagination($items, $perPage))->boot();

        $page0 = $pagination->getPage();
        self::assertCount($perPage, $page0);
        self::assertSame(1, current($page0));
        self::assertSame(0, $pagination->getCurrentPage());
        self::assertTrue($pagination->isSatisfied());
        self::assertTrue($pagination->isFirstPage());

        $page1 = $pagination->nextPage();
        self::assertCount($perPage, $page1);
        self::assertSame(5, current($page1));
        self::assertSame(1, $pagination->getCurrentPage());
        self::assertTrue($pagination->isSatisfied());

        $page2 = $pagination->nextPage();
        self::assertCount($perPage, $page2);
        self::assertSame(9, current($page2));
        self::assertSame(2, $pagination->getCurrentPage());
        self::assertTrue($pagination->isSatisfied());

        $page3 = $pagination->nextPage();
        self::assertCount($perPage, $page3);
        self::assertSame(13, current($page3));
        self::assertSame(3, $pagination->getCurrentPage());
        self::assertTrue($pagination->isSatisfied());

        $page4 = $pagination->nextPage();
        self::assertCount(1, $page4);
        self::assertSame(17, current($page4));
        self::assertSame(4, $pagination->getCurrentPage());
        self::assertFalse($pagination->isSatisfied());
        self::assertTrue($pagination->isLastPage());
    }
}
