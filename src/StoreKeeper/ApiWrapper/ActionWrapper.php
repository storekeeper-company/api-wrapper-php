<?php


namespace StoreKeeper\ApiWrapper;
use StoreKeeper\ApiWrapper\Wrapper\WrapperInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;


/**
 * Class ApiWrapper
 * @package StoreKeeper\ApiWrapper
 */
class ActionWrapper implements ActionWrapperInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    /**
     * object the wrapper to use (like SOAPWrapper, JSonWrapper)
     * @var WrapperInterface
     */
    protected $wrapper;

    /**
     * ActionWrapper constructor.
     * @param WrapperInterface|null $wrapper
     */
    function __construct(WrapperInterface $wrapper = null ){
        if( !empty($wrapper)){
            $this->setWrapper($wrapper);
        }
        $this->setLogger(new NullLogger());
    }

    /**
     * @param WrapperInterface $wrapper
     */
    function setWrapper( WrapperInterface $wrapper ) {
        $this->wrapper = $wrapper;
    }

    /**
     * @return \StoreKeeper\ApiWrapper\Wrapper\WrapperInterface
     */
    public function getWrapper()
    {
        return $this->wrapper;
    }
    /**
     * @param $action
     * @param array $params
     * @return mixed
     */
    function callAction($action, array $params = array()) {
        if( is_null($this->wrapper)){
            throw new \LogicException("Wrapper has to be set before the call");
        }
        return $this->wrapper->callAction($action, $params);
    }
}
