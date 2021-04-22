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

    function __construct($input = array(), $flags = 0, $iterator_class = "ArrayIterator")
    {
        if( empty($input)){
            $input['success'] = true;
        }
        parent::__construct($input, $flags, $iterator_class);
    }
    /**
     * @param string|null $id
     *
     * @return string
     */
    function setCallId(string $id = null){
        if( is_null($id) ){
            $id = uniqid();
        }
        $this['call_id'] = $id;
        return $id;
    }
    /**
     *
     */
    function startTimer(){
        $this->time_start = microtime(true);
        $this->timer_running = true;
    }
    /**
     *
     */
    function stopTimer(){
        if( $this->timer_running ){
            $this['time_ms'] = round( (microtime(true) - $this->time_start)* 1000 );
            $this->timer_running = false;
        }
        return $this['time_ms'];
    }
    /**
     * @param \Throwable $e
     * @param Context $context
     *
     * @return Context
     */
    function setThrowable(\Throwable $e,  $trace = true)
    {
        $this['success'] = false;
        $this['exception_class'] = get_class($e);
        $this['exception']       = $e->getMessage();
        if ($e instanceof GeneralException) {
            $this['exception_ref'] = $e->getReference();
        }
        if( $trace ){
            $this['exception_trace'] = $e->getTraceAsString();
        }
    }
    /**
     * @return array
     */
    function toArray(): array {
        return iterator_to_array($this);
    }
}