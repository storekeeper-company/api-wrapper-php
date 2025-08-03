<?php

namespace StoreKeeper\ApiWrapper\Iterator;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ListCallIteratorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider foreachDataProvider
     */
    public function testForeachLoop($mockData, $expectedItems): void
    {
        $callCount = 0;
        $mockCall = function () use ($mockData, &$callCount) {
            ++$callCount;

            return $mockData;
        };

        $iterator = new ListCallIterator($mockCall);

        $items = [];
        foreach ($iterator as $key => $item) {
            $items[$key] = $item;
        }

        $this->assertEquals($expectedItems, $items);
        $this->assertEquals(1, $callCount, 'Call should be executed only once');
    }

    public function foreachDataProvider(): array
    {
        return [
            'simple data' => [
                ['data' => [['id' => 1, 'name' => 'Item 1'], ['id' => 2, 'name' => 'Item 2']], 'count' => 2],
                [['id' => 1, 'name' => 'Item 1'], ['id' => 2, 'name' => 'Item 2']],
            ],
            'empty data' => [
                ['data' => [], 'count' => 0],
                [],
            ],
            'data without count' => [
                ['data' => [['id' => 1], ['id' => 2], ['id' => 3]]],
                [['id' => 1], ['id' => 2], ['id' => 3]],
            ],
            'single item' => [
                ['data' => [['id' => 42, 'value' => 'test']], 'count' => 1],
                [['id' => 42, 'value' => 'test']],
            ],
        ];
    }

    public function testCount(): void
    {
        $mockData = ['data' => [['id' => 1], ['id' => 2], ['id' => 3]], 'count' => 3];
        $mockCall = function () use ($mockData) {
            return $mockData;
        };

        $iterator = new ListCallIterator($mockCall);

        $this->assertCount(3, $iterator);
        $this->assertEquals(3, $iterator->count());
        $this->assertEquals(3, $iterator->getCount());
    }

    public function testCountWithoutCountKey(): void
    {
        $mockData = ['data' => [['id' => 1], ['id' => 2]]];
        $mockCall = function () use ($mockData) {
            return $mockData;
        };

        $iterator = new ListCallIterator($mockCall);

        $this->assertCount(2, $iterator);
        $this->assertEquals(2, $iterator->getCount());
    }

    public function testArrayAccess(): void
    {
        $mockData = ['data' => [['id' => 1, 'name' => 'First'], ['id' => 2, 'name' => 'Second']], 'count' => 2];
        $mockCall = function () use ($mockData) {
            return $mockData;
        };

        $iterator = new ListCallIterator($mockCall);

        // Test offsetExists
        $this->assertTrue($iterator->offsetExists(0));
        $this->assertTrue($iterator->offsetExists(1));
        $this->assertFalse($iterator->offsetExists(2));

        // Test offsetGet
        $this->assertEquals(['id' => 1, 'name' => 'First'], $iterator->offsetGet(0));
        $this->assertEquals(['id' => 2, 'name' => 'Second'], $iterator->offsetGet(1));
        $this->assertNull($iterator[2] ?? null);

        // Test offsetSet
        $iterator->offsetSet(0, ['id' => 10, 'name' => 'Modified']);
        $this->assertEquals(['id' => 10, 'name' => 'Modified'], $iterator->offsetGet(0));

        // Test offsetUnset
        $iterator->offsetUnset(1);
        $this->assertFalse($iterator->offsetExists(1));
    }

    public function testIsExecuted(): void
    {
        $callCount = 0;
        $mockData = ['data' => [['id' => 1]], 'count' => 1];
        $mockCall = function () use ($mockData, &$callCount) {
            ++$callCount;

            return $mockData;
        };

        $iterator = new ListCallIterator($mockCall);

        $this->assertFalse($iterator->isExecuted());
        $this->assertEquals(0, $callCount);

        // Trigger execution
        $iterator->count();

        $this->assertTrue($iterator->isExecuted());
        $this->assertEquals(1, $callCount);

        // Multiple calls should not re-execute
        $iterator->count();
        $iterator->current();
        $iterator->next();

        $this->assertEquals(1, $callCount);
    }

    public function testEmptyData(): void
    {
        $mockData = ['data' => []];
        $mockCall = function () use ($mockData) {
            return $mockData;
        };

        $iterator = new ListCallIterator($mockCall);

        $this->assertCount(0, $iterator);
        $this->assertFalse($iterator->valid());
        $this->assertNull($iterator->current());

        $items = [];
        foreach ($iterator as $item) {
            $items[] = $item;
        }
        $this->assertEmpty($items);
    }

    public function testRewind(): void
    {
        $mockData = ['data' => [['id' => 1], ['id' => 2], ['id' => 3]], 'count' => 3];
        $mockCall = function () use ($mockData) {
            return $mockData;
        };

        $iterator = new ListCallIterator($mockCall);

        // First iteration
        $firstRun = [];
        foreach ($iterator as $key => $item) {
            $firstRun[$key] = $item;
        }

        // Rewind and iterate again
        $iterator->rewind();
        $secondRun = [];
        foreach ($iterator as $key => $item) {
            $secondRun[$key] = $item;
        }

        $this->assertEquals($firstRun, $secondRun);
    }

    public function testIteratorMethods(): void
    {
        $mockData = ['data' => [['id' => 1], ['id' => 2], ['id' => 3]], 'count' => 3];
        $mockCall = function () use ($mockData) {
            return $mockData;
        };

        $iterator = new ListCallIterator($mockCall);

        // Test initial state
        $this->assertTrue($iterator->valid());
        $this->assertEquals(0, $iterator->key());
        $this->assertEquals(['id' => 1], $iterator->current());

        // Test next
        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertEquals(1, $iterator->key());
        $this->assertEquals(['id' => 2], $iterator->current());

        // Test next again
        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertEquals(2, $iterator->key());
        $this->assertEquals(['id' => 3], $iterator->current());

        // Test end of iteration
        $iterator->next();
        $this->assertFalse($iterator->valid());
        $this->assertNull($iterator->current());
        $this->assertNull($iterator->key());
    }

    public function testAssertionErrorWhenDataKeyMissing(): void
    {
        $mockData = ['notdata' => []];
        $mockCall = function () use ($mockData) {
            return $mockData;
        };

        $iterator = new ListCallIterator($mockCall);

        // Since the code now checks empty($result['data']) instead of assert,
        // it will return empty array and count 0
        $this->assertEquals(0, $iterator->count());
        $this->assertEmpty(iterator_to_array($iterator));
    }
}
