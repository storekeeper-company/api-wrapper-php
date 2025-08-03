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

        $this->assertEquals($expectedItems, $items, 'Iterator should return expected items in correct order');
        $this->assertEquals(1, $callCount, 'Backend call should be executed only once (lazy loading)');
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

        $this->assertCount(3, $iterator, 'Countable interface should return correct count');
        $this->assertEquals(3, $iterator->count(), 'count() method should return correct count');
        $this->assertEquals(3, $iterator->getCount(), 'getCount() method should return correct count');
    }

    public function testCountWithoutCountKey(): void
    {
        $mockData = ['data' => [['id' => 1], ['id' => 2]]];
        $mockCall = function () use ($mockData) {
            return $mockData;
        };

        $iterator = new ListCallIterator($mockCall);

        $this->assertCount(2, $iterator, 'Count should be calculated from data array when count key is missing');
        $this->assertEquals(2, $iterator->getCount(), 'getCount() should return data array length when count key is missing');
    }

    public function testArrayAccess(): void
    {
        $mockData = ['data' => [['id' => 1, 'name' => 'First'], ['id' => 2, 'name' => 'Second']], 'count' => 2];
        $mockCall = function () use ($mockData) {
            return $mockData;
        };

        $iterator = new ListCallIterator($mockCall);

        $this->assertTrue($iterator->offsetExists(0), 'First element should exist at offset 0');
        $this->assertTrue($iterator->offsetExists(1), 'Second element should exist at offset 1');
        $this->assertFalse($iterator->offsetExists(2), 'Non-existent offset should return false');

        $this->assertEquals(['id' => 1, 'name' => 'First'], $iterator->offsetGet(0), 'offsetGet(0) should return first element');
        $this->assertEquals(['id' => 2, 'name' => 'Second'], $iterator->offsetGet(1), 'offsetGet(1) should return second element');
        $this->assertNull($iterator[2] ?? null, 'Accessing non-existent offset should return null');

        $iterator->offsetSet(0, ['id' => 10, 'name' => 'Modified']);
        $this->assertEquals(['id' => 10, 'name' => 'Modified'], $iterator->offsetGet(0), 'offsetSet should modify the element at given offset');

        $iterator->offsetUnset(1);
        $this->assertFalse($iterator->offsetExists(1), 'offsetUnset should remove element at given offset');
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

        $this->assertFalse($iterator->isExecuted(), 'Iterator should not be executed before first access');
        $this->assertEquals(0, $callCount, 'Backend should not be called before first access');

        $iterator->count();

        $this->assertTrue($iterator->isExecuted(), 'Iterator should be marked as executed after first access');
        $this->assertEquals(1, $callCount, 'Backend should be called exactly once after first access');

        $iterator->count();
        $iterator->current();
        $iterator->next();

        $this->assertEquals(1, $callCount, 'Multiple iterator operations should not trigger additional backend calls');
    }

    public function testEmptyData(): void
    {
        $mockData = ['data' => []];
        $mockCall = function () use ($mockData) {
            return $mockData;
        };

        $iterator = new ListCallIterator($mockCall);

        $this->assertCount(0, $iterator, 'Empty iterator should have count of 0');
        $this->assertFalse($iterator->valid(), 'Empty iterator should not be valid');
        $this->assertNull($iterator->current(), 'Empty iterator current() should return null');

        $items = [];
        foreach ($iterator as $item) {
            $items[] = $item;
        }
        $this->assertEmpty($items, 'Foreach over empty iterator should yield no items');
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

        $this->assertEquals($firstRun, $secondRun, 'Rewind should allow re-iteration with same results');
    }

    public function testIteratorMethods(): void
    {
        $mockData = ['data' => [['id' => 1], ['id' => 2], ['id' => 3]], 'count' => 3];
        $mockCall = function () use ($mockData) {
            return $mockData;
        };

        $iterator = new ListCallIterator($mockCall);

        $this->assertTrue($iterator->valid(), 'Iterator should be valid at start');
        $this->assertEquals(0, $iterator->key(), 'Initial key should be 0');
        $this->assertEquals(['id' => 1], $iterator->current(), 'Initial current should be first element');

        $iterator->next();
        $this->assertTrue($iterator->valid(), 'Iterator should be valid after first next()');
        $this->assertEquals(1, $iterator->key(), 'Key should be 1 after first next()');
        $this->assertEquals(['id' => 2], $iterator->current(), 'Current should be second element after first next()');

        $iterator->next();
        $this->assertTrue($iterator->valid(), 'Iterator should be valid at last element');
        $this->assertEquals(2, $iterator->key(), 'Key should be 2 at last element');
        $this->assertEquals(['id' => 3], $iterator->current(), 'Current should be third element');

        $iterator->next();
        $this->assertFalse($iterator->valid(), 'Iterator should be invalid after last element');
        $this->assertNull($iterator->current(), 'Current should be null after iteration end');
        $this->assertNull($iterator->key(), 'Key should be null after iteration end');
    }

    public function testAssertionErrorWhenDataKeyMissing(): void
    {
        $mockData = ['notdata' => []];
        $mockCall = function () use ($mockData) {
            return $mockData;
        };

        $iterator = new ListCallIterator($mockCall);

        $this->assertEquals(0, $iterator->count(), 'Iterator should return count 0 when data key is missing');
        $this->assertEmpty(iterator_to_array($iterator), 'Iterator should be empty when data key is missing');
    }
}
