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

        $this->assertCount(2, $items);
        $this->assertEquals(['id' => 1, 'name' => 'Item 1'], $items[0]);
        $this->assertEquals(['id' => 2, 'name' => 'Item 2'], $items[1]);
        $this->assertEquals(1, $callCount, 'Should only make one call for data that fits in one page');
    }

    public function testForeachLoopMultiplePages(): void
    {
        $callCount = 0;
        $pages = [
            // First page - full page (100 items)
            [
                'data' => array_map(fn ($i) => ['id' => $i, 'name' => "Item $i"], range(1, 100)),
                'count' => 100,
            ],
            // Second page - full page (100 items)
            [
                'data' => array_map(fn ($i) => ['id' => $i, 'name' => "Item $i"], range(101, 200)),
                'count' => 100,
            ],
            // Third page - partial page (50 items)
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

        $this->assertCount(250, $items);
        $this->assertEquals(['id' => 1, 'name' => 'Item 1'], $items[0]);
        $this->assertEquals(['id' => 100, 'name' => 'Item 100'], $items[99]);
        $this->assertEquals(['id' => 101, 'name' => 'Item 101'], $items[100]);
        $this->assertEquals(['id' => 250, 'name' => 'Item 250'], $items[249]);
        $this->assertEquals(3, $callCount, 'Should make 3 calls for 250 items');
    }

    public function testMaybeHasMore(): void
    {
        // Test with full page (may have more)
        $mockCall = function () {
            return [
                'data' => array_map(fn ($i) => ['id' => $i], range(1, 100)),
                'count' => 100,
            ];
        };
        $iterator = new ListCallPaginatedIterator($mockCall);
        $iterator->count(); // Trigger execution
        $this->assertTrue($iterator->maybeHasMore());

        // Test with partial page (no more)
        $mockCall2 = function () {
            return [
                'data' => array_map(fn ($i) => ['id' => $i], range(1, 50)),
                'count' => 50,
            ];
        };
        $iterator2 = new ListCallPaginatedIterator($mockCall2);
        $iterator2->count(); // Trigger execution
        $this->assertFalse($iterator2->maybeHasMore());

        // Test with empty result
        $mockCall3 = function () {
            return ['data' => [], 'count' => 0];
        };
        $iterator3 = new ListCallPaginatedIterator($mockCall3);
        $iterator3->count(); // Trigger execution
        $this->assertFalse($iterator3->maybeHasMore());
    }

    public function testSetPerPage(): void
    {
        $callCount = 0;
        $mockCall = function ($iterator) use (&$callCount) {
            ++$callCount;
            $perPage = $iterator->getPerPage();
            $start = $iterator->getStart();

            // Return data based on per_page setting
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
        $iterator->setPerPage(10); // Set smaller page size

        $items = [];
        foreach ($iterator as $item) {
            $items[] = $item;
            if (count($items) >= 30) {
                break; // Stop after 30 items
            }
        }

        $this->assertEquals(10, $iterator->getPerPage());
        $this->assertCount(30, $items);
        $this->assertGreaterThanOrEqual(3, $callCount, 'Should make multiple calls with smaller page size');
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

        $this->assertEquals(0, $iterator->getStart());

        $items = [];
        foreach ($iterator as $item) {
            $items[] = $item;
        }

        $this->assertCount(150, $items);
        $this->assertEquals(100, $iterator->getStart()); // Should be at 100 after first page
    }

    public function testEmptyPages(): void
    {
        $callCount = 0;
        $pages = [
            // First page with data
            ['data' => [['id' => 1], ['id' => 2]], 'count' => 2],
            // Second call returns empty (no more data)
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

        $this->assertCount(2, $items);
        $this->assertEquals(1, $callCount, 'Should stop after receiving partial page');
    }

    public function testPaginationWithExactPageBoundary(): void
    {
        $callCount = 0;
        $pages = [
            // Exactly 100 items (full page)
            ['data' => array_map(fn ($i) => ['id' => $i], range(1, 100)), 'count' => 100],
            // Empty second page
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

        $this->assertCount(100, $items);
        $this->assertEquals(2, $callCount, 'Should make second call to check for more data');
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

        // Count should reflect current page
        $this->assertEquals(100, $iterator->count());

        // Iterate to load next page
        $items = [];
        foreach ($iterator as $item) {
            $items[] = $item;
        }

        $this->assertCount(125, $items);
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

        $this->assertFalse($iterator->isExecuted());

        // Trigger first execution
        $iterator->current();
        $this->assertTrue($iterator->isExecuted());
        $this->assertEquals(1, $callCount);

        // Complete iteration (should not trigger more calls as count < per_page)
        foreach ($iterator as $item) {
            // iterate
        }

        $this->assertEquals(1, $callCount);
    }
}
