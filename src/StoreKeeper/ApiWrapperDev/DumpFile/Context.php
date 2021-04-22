<?php

namespace StoreKeeper\ApiWrapperDev\DumpFile;

use StoreKeeper\ApiWrapper\Exception\GeneralException;

class Context extends \ArrayObject
{
    /**
     * @var int
     */
    protected $time_start;
    /**
     * @var bool
     */
    protected $timer_running = false;

    public function __construct($input = [], $flags = 0, $iterator_class = 'ArrayIterator')
    {
        if (empty($input)) {
            $input['success'] = true;
        }
        parent::__construct($input, $flags, $iterator_class);
    }

    /**
     * @return string
     */
    public function setCallId(string $id = null)
    {
        if (is_null($id)) {
            $id = uniqid();
        }
        $this['call_id'] = $id;

        return $id;
    }

    public function startTimer()
    {
        $this->time_start = microtime(true);
        $this->timer_running = true;
    }

    public function stopTimer()
    {
        if ($this->timer_running) {
            $this['time_ms'] = round((microtime(true) - $this->time_start) * 1000);
            $this->timer_running = false;
        }

        return $this['time_ms'];
    }

    /**
     * @param Context $context
     *
     * @return Context
     */
    public function setThrowable(\Throwable $e, $trace = true)
    {
        $this['success'] = false;
        $this['exception_class'] = get_class($e);
        $this['exception'] = $e->getMessage();
        if ($e instanceof GeneralException) {
            $this['exception_ref'] = $e->getReference();
        }
        if ($trace) {
            $this['exception_trace'] = $e->getTraceAsString();
        }
    }

    public function toArray(): array
    {
        return iterator_to_array($this);
    }
}
