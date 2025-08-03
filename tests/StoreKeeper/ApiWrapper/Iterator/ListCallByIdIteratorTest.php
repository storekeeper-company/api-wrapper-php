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

        $this->assertEquals($expectedItems, $items);
        $this->assertEquals($expectedKeys, $keys);
        $this->assertEquals(1, $callCount, 'Call should be executed only once');
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
        $this->assertEquals([10, 20, 30], $ids);
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
        $this->assertEquals($expectedItems, $items);
        $this->assertEquals(['abc-123', 'def-456'], $iterator->getIds());
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

        // Trigger execution
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

        // Trigger execution
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

        // Trigger execution
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

        // Test offsetExists with ID keys
        $this->assertTrue($iterator->offsetExists(10));
        $this->assertTrue($iterator->offsetExists(20));
        $this->assertFalse($iterator->offsetExists(30));

        // Test offsetGet with ID keys
        $this->assertEquals(['id' => 10, 'name' => 'First'], $iterator->offsetGet(10));
        $this->assertEquals(['id' => 20, 'name' => 'Second'], $iterator->offsetGet(20));
        $this->assertNull($iterator->offsetGet(30));
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

        $this->assertFalse($iterator->isExecuted());
        $this->assertCount(3, $iterator);
        $this->assertTrue($iterator->isExecuted());
        $this->assertEquals(1, $callCount);
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

        // Test with null key (should use default 'id')
        $iterator = new ListCallByIdIterator($mockCall, null);
        $this->assertEquals([1], $iterator->getIds());

        // Test with empty string (should use default 'id')
        $iterator2 = new ListCallByIdIterator($mockCall, '');
        $this->assertEquals([1], $iterator2->getIds());
    }
}
