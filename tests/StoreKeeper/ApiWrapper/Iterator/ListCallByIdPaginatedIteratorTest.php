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
            [
                'data' => [
                    ['id' => 10, 'name' => 'Item 10'],
                    ['id' => 20, 'name' => 'Item 20'],
                    ['id' => 30, 'name' => 'Item 30'],
                ],
                'count' => 3,
            ],
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
        $iterator->setPerPage(3);

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

        $this->assertEquals($expectedItems, $items, 'Items should be keyed by ID across all pages');
        $this->assertEquals([10, 20, 30, 40, 50], $keys, 'Keys should contain all IDs in order');
        $this->assertEquals(2, $callCount, 'Should make 2 backend calls for paginated data');
    }

    public function testGetIdsAcrossPages(): void
    {
        $callCount = 0;
        $pages = [
            [
                'data' => array_map(fn ($i) => ['id' => $i * 10, 'value' => "Val$i"], range(1, 100)),
                'count' => 100,
            ],
            [
                'data' => array_map(fn ($i) => ['id' => $i * 10, 'value' => "Val$i"], range(101, 150)),
                'count' => 50,
            ],
        ];

        $mockCall = function () use (&$callCount, $pages) {
            return $pages[$callCount++];
        };

        $iterator = new ListCallByIdPaginatedIterator($mockCall);

        $idsBeforeIteration = $iterator->getIds();
        $this->assertCount(100, $idsBeforeIteration, 'getIds() should return 100 IDs from first page before iteration');

        $items = [];
        foreach ($iterator as $id => $item) {
            $items[$id] = $item;
        }

        $idsAfterIteration = $iterator->getIds();
        $this->assertCount(50, $idsAfterIteration, 'getIds() should return only 50 IDs from last loaded page');

        $expectedIds = array_map(fn ($i) => $i * 10, range(101, 150));
        $this->assertEquals($expectedIds, $idsAfterIteration, 'IDs should match expected values from last page');
    }

    public function testIdContinuityAcrossPages(): void
    {
        $callCount = 0;
        $mockCall = function ($iterator) use (&$callCount) {
            ++$callCount;
            $start = $iterator->getStart();
            $perPage = $iterator->getPerPage();

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
                break;
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
                'count' => 3,
            ],
            [
                'data' => [
                    ['uuid' => 'def-4', 'name' => 'Fourth'],
                    ['uuid' => 'def-5', 'name' => 'Fifth'],
                ],
                'count' => 2,
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

        $this->assertEquals($expectedItems, $items, 'Items should use custom UUID field as keys');
        $this->assertEquals(['def-4', 'def-5'], $iterator->getIds(), 'getIds() should return UUIDs from last loaded page');
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

        $this->assertCount(2, $items, 'Should return 2 items');
        $this->assertArrayHasKey(100, $items, 'Item with ID 100 should exist');
        $this->assertArrayHasKey(200, $items, 'Item with ID 200 should exist');
        $this->assertFalse($iterator->maybeHasMore(), 'Should not indicate more data for partial page');
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

        $this->assertEmpty($items, 'Empty page should yield no items');
        $this->assertEquals(1, $callCount, 'Should only make one backend call for empty data');
        $this->assertEmpty($iterator->getIds(), 'getIds() should return empty array for empty data');
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

        $this->assertEquals(0, $iterator->getStart(), 'Initial start offset should be 0');
        $this->assertEquals(3, $iterator->getPerPage(), 'Page size should be 3');
        $this->assertFalse($iterator->isExecuted(), 'Iterator should not be executed initially');

        $first = $iterator->current();
        $this->assertEquals(['id' => 'first-1', 'value' => 1], $first, 'First element should match expected data');
        $this->assertTrue($iterator->isExecuted(), 'Iterator should be executed after current()');
        $this->assertEquals(1, $callCount, 'Should make one backend call after first access');

        $allItems = [];
        foreach ($iterator as $id => $item) {
            $allItems[$id] = $item;
        }

        $this->assertCount(5, $allItems, 'Should load all 5 items across 2 pages');
        $this->assertEquals(2, $callCount, 'Should make 2 backend calls total');
        $this->assertEquals(['second-1', 'second-2'], $iterator->getIds(), 'getIds() should return only IDs from last page');
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

        foreach ($iterator as $item) {
            // iterate to load all pages
        }

        $this->assertFalse($iterator->offsetExists(10), 'ID 10 from first page should not be accessible');
        $this->assertFalse($iterator->offsetExists(20), 'ID 20 from first page should not be accessible');
        $this->assertTrue($iterator->offsetExists(30), 'ID 30 from last page should be accessible');
        $this->assertFalse($iterator->offsetExists(40), 'Non-existent ID 40 should return false');

        $this->assertNull($iterator->offsetGet(10), 'offsetGet(10) should return null for ID from first page');
        $this->assertEquals(['id' => 30, 'val' => 'C'], $iterator->offsetGet(30), 'offsetGet(30) should return item from last page');
    }
}
