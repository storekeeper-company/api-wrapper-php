<?php

namespace StoreKeeper\ApiWrapper;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use StoreKeeper\ApiWrapper\Wrapper\WrapperInterface;

/**
 * Class ApiWrapper.
 */
class ActionWrapper implements ActionWrapperInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    /**
     * object the wrapper to use (like SOAPWrapper, JSonWrapper).
     *
     * @var WrapperInterface
     */
    protected $wrapper;

    /**
     * ActionWrapper constructor.
     */
    public function __construct(?WrapperInterface $wrapper = null)
    {
        if (!empty($wrapper)) {
            $this->setWrapper($wrapper);
        }
        $this->setLogger(new NullLogger());
    }

    public function setWrapper(WrapperInterface $wrapper): void
    {
        $this->wrapper = $wrapper;
    }

    public function getWrapper(): WrapperInterface
    {
        return $this->wrapper;
    }

    public function callAction(string $action, array $params = []): mixed
    {
        if (is_null($this->wrapper)) {
            throw new \LogicException('Wrapper has to be set before the call');
        }

        return $this->wrapper->callAction($action, $params);
    }
}
