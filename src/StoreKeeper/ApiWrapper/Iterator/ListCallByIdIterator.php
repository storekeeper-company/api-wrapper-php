<?php

namespace StoreKeeper\ApiWrapper\Iterator;

class ListCallByIdIterator extends ListCallIterator
{
    protected ?string $key = 'id';

    protected array $keys = [];

    /**
     * ListCallByIdIterator constructor.
     */
    public function __construct(callable $call, ?string $key = null)
    {
        parent::__construct($call);

        if (!empty($key)) {
            $this->key = $key;
        }
    }

    protected function setItFromData(array $data): void
    {
        $by_keys = [];
        foreach ($data as $i => &$datum) {
            if (!isset($datum[$this->key])) {
                throw new \AssertionError("No \'data.{$this->key}\' key in result[$i]");
            }
            $key = $datum[$this->key];
            if (array_key_exists($key, $by_keys)) {
                throw new \AssertionError("Duplicate key '{$this->key}' for result[$i]");
            }
            $by_keys[$key] = &$datum;
        }
        $this->keys = array_keys($by_keys);
        $this->it = new \ArrayIterator($by_keys);
    }

    public function getIds(): array
    {
        $this->ensureResult();

        return $this->keys;
    }
}
