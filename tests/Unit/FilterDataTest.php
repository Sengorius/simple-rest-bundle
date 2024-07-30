<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SkriptManufaktur\SimpleRestBundle\Component\FilterData;
use SkriptManufaktur\SimpleRestBundle\Exception\ApiProcessException;
use SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DummyEntity;
use SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DummyFilterObject;
use Symfony\Component\HttpFoundation\Request;

class FilterDataTest extends TestCase
{
    public function testEmptyCreation(): void
    {
        $filter = new FilterData();
        $filter->boot();

        static::assertInstanceOf(FilterData::class, $filter);
        static::assertIsArray($filter->getSearch());
        static::assertIsArray($filter->getSorting());
        static::assertSame(0, $filter->getPage());
        static::assertSame(0, $filter->getSearch('page'));
    }

    public function testSimpleCreation(): void
    {
        $filter = (new FilterData())
            ->satisfyApiFilterInterface(DummyFilterObject::class)
            ->boot()
        ;

        static::assertInstanceOf(FilterData::class, $filter);
        static::assertIsArray($filter->getSearch());
        static::assertIsArray($filter->getSorting());
        static::assertSame(0, $filter->getPage());
        static::assertSame(0, $filter->getSearch('page'));
        static::assertSame(null, $filter->getSearch('name'));
        static::assertSame(date('Y-m-d'), $filter->getSearch('date'));
        static::assertTrue($filter->getSearch('enabled'));
        static::assertSame('DESC', $filter->getSorting()['date']);
    }

    public function testGetUndefinedDefault(): void
    {
        $filter = (new FilterData())
            ->satisfyApiFilterInterface(DummyFilterObject::class)
            ->boot()
        ;

        static::assertInstanceOf(FilterData::class, $filter);
        static::assertIsArray($filter->getSearch());
        static::assertIsArray($filter->getSorting());

        static::expectException(ApiProcessException::class);
        static::expectExceptionMessage('FilterData: Option "undef" is not configured!');
        $filter->getSearch('undef');
    }

    public function testUnbootedFilterData(): void
    {
        $filter = FilterData::createFromAttributes(DummyFilterObject::class);

        static::assertInstanceOf(FilterData::class, $filter);
        static::assertIsArray($filter->getSearch());
        static::assertNotEmpty($filter->getSearch());
        static::assertIsArray($filter->getSorting());
        static::assertNotEmpty($filter->getSorting());
        static::assertNull($filter->getSearch('name'));
    }

    public function testSatisfiedFilterData(): void
    {
        $sort = ['date' => null, 'rank' => 'asc'];
        $data = [
            'name' => 'Sam',
            'rank' => [
                'gte' => 2,
            ],
            'page' => 2,
        ];

        $filter = FilterData::createFromAttributes(DummyFilterObject::class, $data, $sort)
            ->boot()
        ;

        static::assertInstanceOf(FilterData::class, $filter);
        static::assertIsArray($filter->getSearch());
        static::assertIsArray($filter->getSorting());
        static::assertSame(1, $filter->getPage());
        static::assertSame(1, $filter->getSearch('page'));
        static::assertSame('Sam', $filter->getSearch('name'));
        static::assertSame(date('Y-m-d'), $filter->getSearch('date'));
        static::assertSame(['gte' => 2], $filter->getSearch('rank'));
        static::assertTrue($filter->getSearch('enabled'));
        static::assertSame('ASC', $filter->getSorting('rank'));
        static::assertNull($filter->getSorting('date'));
    }

    public function testSerialization(): void
    {
        $sort = ['date' => null, 'rank' => 'asc'];
        $data = [
            'name' => 'Sam',
            'rank' => [
                'gte' => 2,
            ],
            'page' => 2,
        ];

        $filter = FilterData::createFromAttributes(DummyFilterObject::class, $data, $sort);

        $expectedSerialized = 'O:54:"SkriptManufaktur\SimpleRestBundle\Component\FilterData":3:{s:6:"search";a:5:{s:4:"date";s:10:"'.date('Y-m-d').'";s:7:"enabled";b:1;s:4:"page";i:1;s:4:"name";s:3:"Sam";s:4:"rank";a:1:{s:3:"gte";i:2;}}s:4:"sort";a:2:{s:4:"date";N;s:4:"rank";s:3:"ASC";}s:9:"interface";s:66:"SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DummyFilterObject";}';
        $serializedFilter = serialize($filter);
        static::assertIsString($serializedFilter);
        static::assertSame($expectedSerialized, $serializedFilter);

        $unserializedFilter = unserialize($serializedFilter);
        static::assertInstanceOf(FilterData::class, $unserializedFilter);
        static::assertIsArray($filter->getSearch());
        static::assertIsArray($filter->getSorting());
        static::assertSame(1, $filter->getPage());
        static::assertSame(1, $filter->getSearch('page'));
        static::assertSame('Sam', $filter->getSearch('name'));
        static::assertSame(date('Y-m-d'), $filter->getSearch('date'));
        static::assertSame(['gte' => 2], $filter->getSearch('rank'));
        static::assertTrue($filter->getSearch('enabled'));
        static::assertSame('ASC', $filter->getSorting('rank'));
        static::assertNull($filter->getSorting('date'));
    }

    public function testCustomSerialization(): void
    {
        $sort = ['date' => null, 'rank' => 'asc'];
        $data = [
            'name' => 'Sam',
            'rank' => [
                'gte' => 2,
            ],
            'page' => 2,
        ];

        $filter = FilterData::createFromAttributes(DummyFilterObject::class, $data, $sort);

        $expectedSerialized = '{"search":{"date":"'.date('Y-m-d').'","enabled":true,"page":1,"name":"Sam","rank":{"gte":2}},"sort":{"date":null,"rank":"ASC"},"interface":"SkriptManufaktur\\\\SimpleRestBundle\\\\Tests\\\\Fixtures\\\\DummyFilterObject"}';
        $serializedFilter = $filter->serialize();
        static::assertIsString($serializedFilter);
        static::assertSame($expectedSerialized, $serializedFilter);

        $unserializedFilter = new FilterData();
        $unserializedFilter->unserialize($serializedFilter);
        static::assertInstanceOf(FilterData::class, $unserializedFilter);
        static::assertIsArray($filter->getSearch());
        static::assertIsArray($filter->getSorting());
        static::assertSame(1, $filter->getPage());
        static::assertSame(1, $filter->getSearch('page'));
        static::assertSame('Sam', $filter->getSearch('name'));
        static::assertSame(date('Y-m-d'), $filter->getSearch('date'));
        static::assertSame(['gte' => 2], $filter->getSearch('rank'));
        static::assertTrue($filter->getSearch('enabled'));
        static::assertSame('ASC', $filter->getSorting('rank'));
        static::assertNull($filter->getSorting('date'));
    }

    public function testSatisfiedFromRequest(): void
    {
        $sort = ['date' => null, 'rank' => 'asc'];
        $data = [
            'name' => 'Sam',
            'rank' => [
                'gte' => 2,
            ],
            'page' => 2,
            'sort' => $sort,
        ];
        $filter = FilterData::createFromRequest(DummyFilterObject::class, new Request($data))
            ->boot()
        ;

        static::assertInstanceOf(FilterData::class, $filter);
        static::assertIsArray($filter->getSearch());
        static::assertIsArray($filter->getSorting());
        static::assertSame(1, $filter->getPage());
        static::assertSame(1, $filter->getSearch('page'));
        static::assertSame('Sam', $filter->getSearch('name'));
        static::assertSame(date('Y-m-d'), $filter->getSearch('date'));
        static::assertSame(['gte' => 2], $filter->getSearch('rank'));
        static::assertTrue($filter->getSearch('enabled'));
        static::assertSame('ASC', $filter->getSorting('rank'));
        static::assertNull($filter->getSorting('date'));
    }

    public function testSatisfyNonExistingClass(): void
    {
        static::expectException(ApiProcessException::class);
        static::expectExceptionMessage('"App\\NotEexisting\\DummyFilterObject" is not a valid FQCN (class string)!');
        FilterData::createFromAttributes('App\\NotEexisting\\DummyFilterObject');
    }

    public function testSatisfyNotAnApiFilterInterface(): void
    {
        static::expectException(ApiProcessException::class);
        static::expectExceptionMessage('Class "SkriptManufaktur\\SimpleRestBundle\\Tests\\Fixtures\\DummyEntity" does not implement interface SkriptManufaktur\\SimpleRestBundle\\Component\\ApiFilterInterface and can therefore not be used as a filter!');
        FilterData::createFromAttributes(DummyEntity::class);
    }
}
