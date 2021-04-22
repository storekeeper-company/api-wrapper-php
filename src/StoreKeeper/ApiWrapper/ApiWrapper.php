<?php

namespace StoreKeeper\ApiWrapper;

use StoreKeeper\ApiWrapper\Wrapper\WrapperInterface;

class ApiWrapper extends ActionWrapper implements ApiWrapperInterface
{
    /**
     * array of authentication info
     * shared among all module wrappers.
     *
     * @var Auth
     */
    protected $auth;

    public function __construct(WrapperInterface $wrapper = null, Auth $auth = null)
    {
        parent::__construct($wrapper);
        if (!empty($auth)) {
            $this->setAuth($auth);
        }
    }

    public function setAuth(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * @return \StoreKeeper\ApiWrapper\Auth
     */
    public function getAuth(): Auth
    {
        return $this->auth;
    }

    /**
     * @param $module_name
     * @param $name
     * @param $params
     * @param Auth $auth
     *
     * @return mixed
     */
    public function callFunction($module_name, $name, array $params = [], Auth $auth = null)
    {
        if (is_null($auth)) {
            $auth = $this->auth;
        }
        if (is_null($auth)) {
            throw new \LogicException('Auth cannot be empty for the call');
        }
        if (is_null($this->wrapper)) {
            throw new \LogicException('Wrapper has to be set before the call');
        }

        $this->logger->debug('callFunction', [
            'action' => $module_name,
            'name' => $name,
        ]);

        return $this->wrapper->call(
            $module_name, $name, $params,
            $auth);
    }

    /**
     * @param $module_name
     * @param Auth $auth
     */
    public function getModule($module_name, Auth $auth = null): ModuleApiWrapperInterface
    {
        return new ModuleApiWrapper($this, $module_name, $auth);
    }

    public function __get($name)
    {
        return $this->getModule($name);
    }
}
