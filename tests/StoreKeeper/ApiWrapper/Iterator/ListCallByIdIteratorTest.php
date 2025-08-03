<?php

namespace StoreKeeper\ApiWrapper\Iterator;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ListCallByIdIteratorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider foreachWithIdsDataProvider
     */
    public function testForeachLoopWithIds($mockData, $expectedItems, $expectedKeys): void
    {
        $callCount = 0;
        $mockCall = function () use ($mockData, &$callCount) {
            ++$callCount;

            return $mockData;
        };

        $iterator = new ListCallByIdIterator($mockCall);

        $items = [];
        $keys = [];
        foreach ($iterator as $key => $item) {
            $items[$key] = $item;
            $keys[] = $key;
        }

        $this->assertEquals($expectedItems, $items, 'Items should be keyed by ID field');
        $this->assertEquals($expectedKeys, $keys, 'Iterator keys should match ID values');
        $this->assertEquals(1, $callCount, 'Backend call should be executed only once (lazy loading)');
    }

    public function foreachWithIdsDataProvider(): array
    {
        return [
            'simple data with ids' => [
                ['data' => [['id' => 10, 'name' => 'Item 1'], ['id' => 20, 'name' => 'Item 2']], 'count' => 2],
                [10 => ['id' => 10, 'name' => 'Item 1'], 20 => ['id' => 20, 'name' => 'Item 2']],
                [10, 20],
            ],
            'empty data' => [
                ['data' => [], 'count' => 0],
                [],
                [],
            ],
            'non-sequential ids' => [
                ['data' => [['id' => 5], ['id' => 100], ['id' => 42]]],
                [5 => ['id' => 5], 100 => ['id' => 100], 42 => ['id' => 42]],
                [5, 100, 42],
            ],
            'string ids' => [
                ['data' => [['id' => 'abc', 'value' => 1], ['id' => 'xyz', 'value' => 2]], 'count' => 2],
                ['abc' => ['id' => 'abc', 'value' => 1], 'xyz' => ['id' => 'xyz', 'value' => 2]],
                ['abc', 'xyz'],
            ],
        ];
    }

    public function testGetIds(): void
    {
        $mockData = [
            'data' => [
                ['id' => 10, 'name' => 'First'],
                ['id' => 20, 'name' => 'Second'],
                ['id' => 30, 'name' => 'Third'],
            ],
            'count' => 3,
        ];
        $mockCall = function () use ($mockData) {
            return $mockData;
        };

        $iterator = new ListCallByIdIterator($mockCall);

        $ids = $iterator->getIds();
        $this->assertEquals([10, 20, 30], $ids, 'getIds() should return all ID values in order');
    }

    public function testCustomKeyField(): void
    {
        $mockData = [
            'data' => [
                ['uuid' => 'abc-123', 'name' => 'First'],
                ['uuid' => 'def-456', 'name' => 'Second'],
            ],
            'count' => 2,
        ];
        $mockCall = function () use ($mockData) {
            return $mockData;
        };

        $iterator = new ListCallByIdIterator($mockCall, 'uuid');

        $items = [];
        foreach ($iterator as $key => $item) {
            $items[$key] = $item;
        }

        $expectedItems = [
            'abc-123' => ['uuid' => 'abc-123', 'name' => 'First'],
            'def-456' => ['uuid' => 'def-456', 'name' => 'Second'],
        ];
        $this->assertEquals($expectedItems, $items, 'Iterator should use custom key field for array keys');
        $this->assertEquals(['abc-123', 'def-456'], $iterator->getIds(), 'getIds() should return values from custom key field');
    }

    public function testDuplicateKeyError(): void
    {
        $mockData = [
            'data' => [
                ['id' => 10, 'name' => 'First'],
                ['id' => 10, 'name' => 'Duplicate'],
            ],
        ];
        $mockCall = function () use ($mockData) {
            return $mockData;
        };

        $iterator = new ListCallByIdIterator($mockCall);

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage("Duplicate key 'id' for result[1]");

        $iterator->current();
    }

    public function testMissingKeyError(): void
    {
        $mockData = [
            'data' => [
                ['id' => 10, 'name' => 'First'],
                ['name' => 'No ID'],
            ],
        ];
        $mockCall = function () use ($mockData) {
            return $mockData;
        };

        $iterator = new ListCallByIdIterator($mockCall);

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage("No 'data.id' key in result[1]");

        $iterator->current();
    }

    public function testMissingCustomKeyError(): void
    {
        $mockData = [
            'data' => [
                ['uuid' => 'abc-123', 'name' => 'First'],
                ['name' => 'No UUID'],
            ],
        ];
        $mockCall = function () use ($mockData) {
            return $mockData;
        };

        $iterator = new ListCallByIdIterator($mockCall, 'uuid');

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage("No 'data.uuid' key in result[1]");

        $iterator->current();
    }

    public function testArrayAccessWithIds(): void
    {
        $mockData = [
            'data' => [
                ['id' => 10, 'name' => 'First'],
                ['id' => 20, 'name' => 'Second'],
            ],
            'count' => 2,
        ];
        $mockCall = function () use ($mockData) {
            return $mockData;
        };

        $iterator = new ListCallByIdIterator($mockCall);

        $this->assertTrue($iterator->offsetExists(10), 'offsetExists should return true for existing ID 10');
        $this->assertTrue($iterator->offsetExists(20), 'offsetExists should return true for existing ID 20');
        $this->assertFalse($iterator->offsetExists(30), 'offsetExists should return false for non-existent ID 30');

        $this->assertEquals(['id' => 10, 'name' => 'First'], $iterator->offsetGet(10), 'offsetGet(10) should return item with ID 10');
        $this->assertEquals(['id' => 20, 'name' => 'Second'], $iterator->offsetGet(20), 'offsetGet(20) should return item with ID 20');
        $this->assertNull($iterator->offsetGet(30), 'offsetGet(30) should return null for non-existent ID');
    }

    public function testCountAndIsExecuted(): void
    {
        $callCount = 0;
        $mockData = [
            'data' => [
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
            ],
            'count' => 3,
        ];
        $mockCall = function () use ($mockData, &$callCount) {
            ++$callCount;

            return $mockData;
        };

        $iterator = new ListCallByIdIterator($mockCall);

        $this->assertFalse($iterator->isExecuted(), 'Iterator should not be executed before first access');
        $this->assertCount(3, $iterator, 'Count should return 3 items');
        $this->assertTrue($iterator->isExecuted(), 'Iterator should be marked as executed after count()');
        $this->assertEquals(1, $callCount, 'Backend should be called exactly once');
    }

    public function testEmptyConstructorKeyParameter(): void
    {
        $mockData = [
            'data' => [['id' => 1]],
            'count' => 1,
        ];
        $mockCall = function () use ($mockData) {
            return $mockData;
        };

        $iterator = new ListCallByIdIterator($mockCall, null);
        $this->assertEquals([1], $iterator->getIds(), 'Null key parameter should default to "id" field');

        $iterator2 = new ListCallByIdIterator($mockCall, '');
        $this->assertEquals([1], $iterator2->getIds(), 'Empty string key parameter should default to "id" field');
    }
}
