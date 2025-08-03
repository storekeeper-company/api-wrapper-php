<?php

namespace StoreKeeper\ApiWrapper\Iterator;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ListCallByIdPaginatedIteratorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testForeachLoopWithPaginatedIds(): void
    {
        $callCount = 0;
        $pages = [
            // First page
            [
                'data' => [
                    ['id' => 10, 'name' => 'Item 10'],
                    ['id' => 20, 'name' => 'Item 20'],
                    ['id' => 30, 'name' => 'Item 30'],
                ],
                'count' => 3,
            ],
            // Second page
            [
                'data' => [
                    ['id' => 40, 'name' => 'Item 40'],
                    ['id' => 50, 'name' => 'Item 50'],
                ],
                'count' => 2,
            ],
        ];

        $mockCall = function () use (&$callCount, $pages) {
            return $pages[$callCount++];
        };

        $iterator = new ListCallByIdPaginatedIterator($mockCall);
        $iterator->setPerPage(3); // Small page size to trigger pagination

        $items = [];
        $keys = [];
        foreach ($iterator as $key => $item) {
            $items[$key] = $item;
            $keys[] = $key;
        }

        $expectedItems = [
            10 => ['id' => 10, 'name' => 'Item 10'],
            20 => ['id' => 20, 'name' => 'Item 20'],
            30 => ['id' => 30, 'name' => 'Item 30'],
            40 => ['id' => 40, 'name' => 'Item 40'],
            50 => ['id' => 50, 'name' => 'Item 50'],
        ];

        $this->assertEquals($expectedItems, $items);
        $this->assertEquals([10, 20, 30, 40, 50], $keys);
        $this->assertEquals(2, $callCount, 'Should make 2 calls for paginated data');
    }

    public function testGetIdsAcrossPages(): void
    {
        $callCount = 0;
        $pages = [
            // First page - full page
            [
                'data' => array_map(fn ($i) => ['id' => $i * 10, 'value' => "Val$i"], range(1, 100)),
                'count' => 100,
            ],
            // Second page - partial page
            [
                'data' => array_map(fn ($i) => ['id' => $i * 10, 'value' => "Val$i"], range(101, 150)),
                'count' => 50,
            ],
        ];

        $mockCall = function () use (&$callCount, $pages) {
            return $pages[$callCount++];
        };

        $iterator = new ListCallByIdPaginatedIterator($mockCall);

        // First, get IDs before full iteration
        $idsBeforeIteration = $iterator->getIds();
        $this->assertCount(100, $idsBeforeIteration); // Only first page loaded

        // Now iterate through all items
        $items = [];
        foreach ($iterator as $id => $item) {
            $items[$id] = $item;
        }

        // Get IDs after full iteration - only returns IDs from current page
        $idsAfterIteration = $iterator->getIds();
        $this->assertCount(50, $idsAfterIteration); // Only last page IDs

        // Verify last page IDs
        $expectedIds = array_map(fn ($i) => $i * 10, range(101, 150));
        $this->assertEquals($expectedIds, $idsAfterIteration);
    }

    public function testIdContinuityAcrossPages(): void
    {
        $callCount = 0;
        $mockCall = function ($iterator) use (&$callCount) {
            ++$callCount;
            $start = $iterator->getStart();
            $perPage = $iterator->getPerPage();

            // Generate unique IDs based on page
            $baseId = ($start / $perPage * 1000) + 1;
            $data = array_map(
                fn ($i) => ['id' => $baseId + $i, 'page' => $callCount],
                range(0, $perPage - 1)
            );

            return [
                'data' => $data,
                'count' => count($data),
            ];
        };

        $iterator = new ListCallByIdPaginatedIterator($mockCall);
        $iterator->setPerPage(10);

        $seenIds = [];
        $pageCount = 0;
        foreach ($iterator as $id => $item) {
            $this->assertNotContains($id, $seenIds, "ID $id should not be duplicated");
            $seenIds[] = $id;

            if (10 === count($seenIds) % 10 && count($seenIds) < 30) {
                ++$pageCount;
            }

            if (count($seenIds) >= 30) {
                break; // Stop after 3 pages
            }
        }

        $this->assertCount(30, array_unique($seenIds), 'All IDs should be unique');
        $this->assertEquals(3, $callCount, 'Should have made 3 page requests');
    }

    public function testCustomKeyFieldWithPagination(): void
    {
        $callCount = 0;
        $pages = [
            [
                'data' => [
                    ['uuid' => 'abc-1', 'name' => 'First'],
                    ['uuid' => 'abc-2', 'name' => 'Second'],
                    ['uuid' => 'abc-3', 'name' => 'Third'],
                ],
                'count' => 3,  // Full page - will trigger pagination
            ],
            [
                'data' => [
                    ['uuid' => 'def-4', 'name' => 'Fourth'],
                    ['uuid' => 'def-5', 'name' => 'Fifth'],
                ],
                'count' => 2,  // Less than per_page - no more pages
            ],
        ];

        $mockCall = function () use (&$callCount, $pages) {
            if ($callCount < count($pages)) {
                return $pages[$callCount++];
            }

            return ['data' => [], 'count' => 0];
        };

        $iterator = new ListCallByIdPaginatedIterator($mockCall, 'uuid');
        $iterator->setPerPage(3);

        $items = [];
        foreach ($iterator as $key => $item) {
            $items[$key] = $item;
        }

        $expectedItems = [
            'abc-1' => ['uuid' => 'abc-1', 'name' => 'First'],
            'abc-2' => ['uuid' => 'abc-2', 'name' => 'Second'],
            'abc-3' => ['uuid' => 'abc-3', 'name' => 'Third'],
            'def-4' => ['uuid' => 'def-4', 'name' => 'Fourth'],
            'def-5' => ['uuid' => 'def-5', 'name' => 'Fifth'],
        ];

        $this->assertEquals($expectedItems, $items);
        // getIds() only returns IDs from the last loaded page
        $this->assertEquals(['def-4', 'def-5'], $iterator->getIds());
    }

    public function testSinglePageWithFullData(): void
    {
        $mockCall = function () {
            return [
                'data' => [
                    ['id' => 100, 'name' => 'Item 100'],
                    ['id' => 200, 'name' => 'Item 200'],
                ],
                'count' => 2,
            ];
        };

        $iterator = new ListCallByIdPaginatedIterator($mockCall);

        $items = [];
        foreach ($iterator as $id => $item) {
            $items[$id] = $item;
        }

        $this->assertCount(2, $items);
        $this->assertArrayHasKey(100, $items);
        $this->assertArrayHasKey(200, $items);
        $this->assertFalse($iterator->maybeHasMore());
    }

    public function testEmptyFirstPage(): void
    {
        $callCount = 0;
        $mockCall = function () use (&$callCount) {
            ++$callCount;

            return ['data' => [], 'count' => 0];
        };

        $iterator = new ListCallByIdPaginatedIterator($mockCall);

        $items = [];
        foreach ($iterator as $item) {
            $items[] = $item;
        }

        $this->assertEmpty($items);
        $this->assertEquals(1, $callCount, 'Should only make one call for empty data');
        $this->assertEmpty($iterator->getIds());
    }

    public function testPaginationBehaviorWithMethods(): void
    {
        $callCount = 0;
        $mockCall = function ($iterator) use (&$callCount) {
            ++$callCount;
            $start = $iterator->getStart();

            if (0 === $start) {
                return [
                    'data' => [
                        ['id' => 'first-1', 'value' => 1],
                        ['id' => 'first-2', 'value' => 2],
                        ['id' => 'first-3', 'value' => 3],
                    ],
                    'count' => 3,
                ];
            }

            return [
                'data' => [
                    ['id' => 'second-1', 'value' => 4],
                    ['id' => 'second-2', 'value' => 5],
                ],
                'count' => 2,
            ];
        };

        $iterator = new ListCallByIdPaginatedIterator($mockCall);
        $iterator->setPerPage(3);

        // Test initial state
        $this->assertEquals(0, $iterator->getStart());
        $this->assertEquals(3, $iterator->getPerPage());
        $this->assertFalse($iterator->isExecuted());

        // Access first element
        $first = $iterator->current();
        $this->assertEquals(['id' => 'first-1', 'value' => 1], $first);
        $this->assertTrue($iterator->isExecuted());
        $this->assertEquals(1, $callCount);

        // Complete iteration
        $allItems = [];
        foreach ($iterator as $id => $item) {
            $allItems[$id] = $item;
        }

        $this->assertCount(5, $allItems);
        $this->assertEquals(2, $callCount);
        // getIds() only returns IDs from the last loaded page
        $this->assertEquals(['second-1', 'second-2'], $iterator->getIds());
    }

    public function testArrayAccessWithPaginatedIds(): void
    {
        $pages = [
            ['data' => [['id' => 10, 'val' => 'A'], ['id' => 20, 'val' => 'B']], 'count' => 2],
            ['data' => [['id' => 30, 'val' => 'C']], 'count' => 1],
        ];

        $callCount = 0;
        $mockCall = function () use (&$callCount, $pages) {
            return $pages[$callCount++];
        };

        $iterator = new ListCallByIdPaginatedIterator($mockCall);
        $iterator->setPerPage(2);

        // Force load all pages
        foreach ($iterator as $item) {
            // iterate
        }

        // Test array access with ID keys - only last page is accessible
        $this->assertFalse($iterator->offsetExists(10)); // From first page
        $this->assertFalse($iterator->offsetExists(20)); // From first page
        $this->assertTrue($iterator->offsetExists(30)); // From last page
        $this->assertFalse($iterator->offsetExists(40));

        $this->assertNull($iterator->offsetGet(10)); // From first page
        $this->assertEquals(['id' => 30, 'val' => 'C'], $iterator->offsetGet(30)); // From last page
    }
}
