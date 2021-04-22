<?php

namespace StoreKeeper\ApiWrapper\Iterator;

class ListCallIterator implements \Iterator, \Countable, \ArrayAccess
{
    /**
     * @var callable
     */
    protected $call;
    /**
     * @var bool
     */
    protected $executed = false;
    /**
     * @var int
     */
    protected $count;
    /**
     * @var \ArrayIterator
     */
    protected $it;

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
    protected function ensureResult()
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

    /**
     * @return bool
     */
    protected function setExecuted(bool $executed): void
    {
        $this->executed = $executed;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @return int
     */
    public function count()
    {
        $this->ensureResult();

        return $this->count;
    }

    public function current()
    {
        $this->ensureResult();

        return $this->it->current();
    }

    public function next()
    {
        $this->ensureResult();
        $this->it->next();
    }

    public function key()
    {
        $this->ensureResult();

        return $this->it->key();
    }

    public function valid()
    {
        $this->ensureResult();

        return $this->it->valid();
    }

    public function rewind()
    {
        $this->ensureResult();
        $this->it->rewind();
    }

    public function offsetSet($offset, $value)
    {
        $this->ensureResult();
        $this->it->offsetSet($offset, $value);
    }

    public function offsetExists($offset)
    {
        $this->ensureResult();

        return $this->it->offsetExists($offset);
    }

    public function offsetUnset($offset)
    {
        $this->ensureResult();
        $this->it->offsetUnset($offset);
    }

    public function offsetGet($offset)
    {
        $this->ensureResult();

        return $this->it->offsetGet($offset);
    }

    protected function executeCall(): void
    {
        $result = $this->doCall();
        $data = $this->getDataFromResult($result);
        $this->setItFromData($data);
    }

    /**
     * @param $data
     */
    protected function setItFromData(array $data): void
    {
        $this->it = new \ArrayIterator($data);
    }

    /**
     * For extending.
     */
    protected function doCall(): array
    {
        return ($this->call)($this);
    }

    /**
     * @return mixed
     */
    protected function getDataFromResult(array $result)
    {
        if (!array_key_exists('data', $result)) {
            throw new \AssertionError('No \'data\' key in result');
        }
        if (!array_key_exists('count', $result)) {
            $this->count = count($result['data']);
        } else {
            $this->count = (int) $result['count'];
        }
        $data = &$result['data'];

        return $data;
    }
}
