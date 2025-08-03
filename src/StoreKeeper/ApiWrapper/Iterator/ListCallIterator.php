<?php

namespace StoreKeeper\ApiWrapper\Iterator;

class ListCallIterator implements \Iterator, \Countable, \ArrayAccess
{
    /**
     * @var callable
     */
    protected $call;
    protected bool $executed = false;
    protected int $count;
    protected \ArrayIterator $it;

    /**
     * ListCallIterator constructor.
     */
    public function __construct(callable $call)
    {
        $this->call = $call;
    }

    /**
     * call the backend if needed.
     */
    protected function ensureResult(): void
    {
        if (!$this->executed) {
            $this->executeCall();
            $this->executed = true;
        }
    }

    public function isExecuted(): bool
    {
        return $this->executed;
    }

    protected function setExecuted(bool $executed): void
    {
        $this->executed = $executed;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function count(): int
    {
        $this->ensureResult();

        return $this->count;
    }

    public function current(): ?array
    {
        $this->ensureResult();

        return $this->it->current();
    }

    public function next(): void
    {
        $this->ensureResult();
        $this->it->next();
    }

    public function key(): string|int|null
    {
        $this->ensureResult();

        return $this->it->key();
    }

    public function valid(): bool
    {
        $this->ensureResult();

        return $this->it->valid();
    }

    public function rewind(): void
    {
        $this->ensureResult();
        $this->it->rewind();
    }

    public function offsetSet($offset, $value): void
    {
        $this->ensureResult();
        $this->it->offsetSet($offset, $value);
    }

    public function offsetExists($offset): bool
    {
        $this->ensureResult();

        return $this->it->offsetExists($offset);
    }

    public function offsetUnset($offset): void
    {
        $this->ensureResult();
        $this->it->offsetUnset($offset);
    }

    public function offsetGet($offset): ?array
    {
        $this->ensureResult();

        if ($this->it->offsetExists($offset)) {
            return $this->it->offsetGet($offset);
        }

        return null;
    }

    protected function executeCall(): void
    {
        $result = $this->doCall();
        if (is_array($result)) {
            $data = $this->getDataFromResult($result);
            $this->setItFromData($data);
        } else {
            $this->setItFromData([]);
        }
    }

    protected function setItFromData(array $data): void
    {
        $this->it = new \ArrayIterator($data);
    }

    /**
     * For extending.
     */
    protected function doCall(): mixed
    {
        return ($this->call)($this);
    }

    protected function getDataFromResult(array $result): array
    {
        if (empty($result['data'])) {
            $this->count = 0;

            return [];
        }

        if (!array_key_exists('count', $result)) {
            $this->count = count($result['data']);
        } else {
            $this->count = (int) $result['count'];
        }

        return $result['data'];
    }
}
