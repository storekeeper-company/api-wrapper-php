<?php

namespace StoreKeeper\ApiWrapper\Iterator;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ListCallPaginatedIteratorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testForeachLoopSinglePage(): void
    {
        $callCount = 0;
        $mockCall = function () use (&$callCount) {
            ++$callCount;

            return [
                'data' => [
                    ['id' => 1, 'name' => 'Item 1'],
                    ['id' => 2, 'name' => 'Item 2'],
                ],
                'count' => 2,
            ];
        };

        $iterator = new ListCallPaginatedIterator($mockCall);

        $items = [];
        foreach ($iterator as $key => $item) {
            $items[] = $item;
        }

        $this->assertCount(2, $items, 'Single page should return 2 items');
        $this->assertEquals(['id' => 1, 'name' => 'Item 1'], $items[0], 'First item should match expected data');
        $this->assertEquals(['id' => 2, 'name' => 'Item 2'], $items[1], 'Second item should match expected data');
        $this->assertEquals(1, $callCount, 'Should only make one backend call for data that fits in one page');
    }

    public function testForeachLoopMultiplePages(): void
    {
        $callCount = 0;
        $pages = [
            [
                'data' => array_map(fn ($i) => ['id' => $i, 'name' => "Item $i"], range(1, 100)),
                'count' => 100,
            ],
            [
                'data' => array_map(fn ($i) => ['id' => $i, 'name' => "Item $i"], range(101, 200)),
                'count' => 100,
            ],
            [
                'data' => array_map(fn ($i) => ['id' => $i, 'name' => "Item $i"], range(201, 250)),
                'count' => 50,
            ],
        ];

        $mockCall = function ($iterator) use (&$callCount, $pages) {
            $pageIndex = intval($callCount);
            ++$callCount;

            return $pages[$pageIndex];
        };

        $iterator = new ListCallPaginatedIterator($mockCall);

        $items = [];
        foreach ($iterator as $item) {
            $items[] = $item;
        }

        $this->assertCount(250, $items, 'Should load all 250 items across pages');
        $this->assertEquals(['id' => 1, 'name' => 'Item 1'], $items[0], 'First item should be from first page');
        $this->assertEquals(['id' => 100, 'name' => 'Item 100'], $items[99], 'Last item of first page should be ID 100');
        $this->assertEquals(['id' => 101, 'name' => 'Item 101'], $items[100], 'First item of second page should be ID 101');
        $this->assertEquals(['id' => 250, 'name' => 'Item 250'], $items[249], 'Last item should be ID 250');
        $this->assertEquals(3, $callCount, 'Should make 3 backend calls for 250 items split across pages');
    }

    public function testMaybeHasMore(): void
    {
        $mockCall = function () {
            return [
                'data' => array_map(fn ($i) => ['id' => $i], range(1, 100)),
                'count' => 100,
            ];
        };
        $iterator = new ListCallPaginatedIterator($mockCall);
        $iterator->count();
        $this->assertTrue($iterator->maybeHasMore(), 'Full page (100 items) should indicate more data may exist');

        $mockCall2 = function () {
            return [
                'data' => array_map(fn ($i) => ['id' => $i], range(1, 50)),
                'count' => 50,
            ];
        };
        $iterator2 = new ListCallPaginatedIterator($mockCall2);
        $iterator2->count();
        $this->assertFalse($iterator2->maybeHasMore(), 'Partial page (50 items) should indicate no more data');

        $mockCall3 = function () {
            return ['data' => [], 'count' => 0];
        };
        $iterator3 = new ListCallPaginatedIterator($mockCall3);
        $iterator3->count();
        $this->assertFalse($iterator3->maybeHasMore(), 'Empty result should indicate no more data');
    }

    public function testSetPerPage(): void
    {
        $callCount = 0;
        $mockCall = function ($iterator) use (&$callCount) {
            ++$callCount;
            $perPage = $iterator->getPerPage();
            $start = $iterator->getStart();

            $data = array_map(
                fn ($i) => ['id' => $i],
                range($start + 1, $start + $perPage)
            );

            return [
                'data' => $data,
                'count' => count($data),
            ];
        };

        $iterator = new ListCallPaginatedIterator($mockCall);
        $iterator->setPerPage(10);

        $items = [];
        foreach ($iterator as $item) {
            $items[] = $item;
            if (count($items) >= 30) {
                break;
            }
        }

        $this->assertEquals(10, $iterator->getPerPage(), 'getPerPage() should return configured page size');
        $this->assertCount(30, $items, 'Should collect exactly 30 items');
        $this->assertGreaterThanOrEqual(3, $callCount, 'Should make at least 3 backend calls with page size of 10');
    }

    public function testGetStart(): void
    {
        $callCount = 0;
        $mockCall = function ($iterator) use (&$callCount) {
            ++$callCount;
            $start = $iterator->getStart();

            if (0 === $start) {
                return [
                    'data' => array_map(fn ($i) => ['id' => $i], range(1, 100)),
                    'count' => 100,
                ];
            }

            return [
                'data' => array_map(fn ($i) => ['id' => $i], range(101, 150)),
                'count' => 50,
            ];
        };

        $iterator = new ListCallPaginatedIterator($mockCall);

        $this->assertEquals(0, $iterator->getStart(), 'Initial start offset should be 0');

        $items = [];
        foreach ($iterator as $item) {
            $items[] = $item;
        }

        $this->assertCount(150, $items, 'Should load all 150 items across two pages');
        $this->assertEquals(100, $iterator->getStart(), 'Start offset should be 100 after loading first page of 100 items');
    }

    public function testEmptyPages(): void
    {
        $callCount = 0;
        $pages = [
            ['data' => [['id' => 1], ['id' => 2]], 'count' => 2],
            ['data' => [], 'count' => 0],
        ];

        $mockCall = function () use (&$callCount, $pages) {
            if ($callCount < count($pages)) {
                return $pages[$callCount++];
            }

            return ['data' => [], 'count' => 0];
        };

        $iterator = new ListCallPaginatedIterator($mockCall);

        $items = [];
        foreach ($iterator as $item) {
            $items[] = $item;
        }

        $this->assertCount(2, $items, 'Should return only 2 items from first page');
        $this->assertEquals(1, $callCount, 'Should stop pagination after receiving partial page');
    }

    public function testPaginationWithExactPageBoundary(): void
    {
        $callCount = 0;
        $pages = [
            ['data' => array_map(fn ($i) => ['id' => $i], range(1, 100)), 'count' => 100],
            ['data' => [], 'count' => 0],
        ];

        $mockCall = function () use (&$callCount, $pages) {
            return $pages[$callCount++];
        };

        $iterator = new ListCallPaginatedIterator($mockCall);

        $items = [];
        foreach ($iterator as $item) {
            $items[] = $item;
        }

        $this->assertCount(100, $items, 'Should return exactly 100 items');
        $this->assertEquals(2, $callCount, 'Should make second call to check for more data when page is exactly full');
    }

    public function testCountWithPagination(): void
    {
        $mockCall = function ($iterator) {
            $start = $iterator->getStart();
            if (0 === $start) {
                return [
                    'data' => array_map(fn ($i) => ['id' => $i], range(1, 100)),
                    'count' => 100,
                ];
            }

            return [
                'data' => array_map(fn ($i) => ['id' => $i], range(101, 125)),
                'count' => 25,
            ];
        };

        $iterator = new ListCallPaginatedIterator($mockCall);

        $this->assertEquals(100, $iterator->count(), 'count() should return current page count (100)');

        $items = [];
        foreach ($iterator as $item) {
            $items[] = $item;
        }

        $this->assertCount(125, $items, 'Should load all 125 items across both pages');
    }

    public function testIsExecutedWithPagination(): void
    {
        $callCount = 0;
        $mockCall = function () use (&$callCount) {
            ++$callCount;

            return [
                'data' => array_map(fn ($i) => ['id' => $i], range(1, 50)),
                'count' => 50,
            ];
        };

        $iterator = new ListCallPaginatedIterator($mockCall);

        $this->assertFalse($iterator->isExecuted(), 'Iterator should not be executed before first access');

        $iterator->current();
        $this->assertTrue($iterator->isExecuted(), 'Iterator should be marked as executed after current()');
        $this->assertEquals(1, $callCount, 'Should make one backend call on first access');

        foreach ($iterator as $item) {
            // iterate
        }

        $this->assertEquals(1, $callCount, 'Should not trigger additional calls when data fits in single page');
    }
}
