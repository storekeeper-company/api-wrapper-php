<?php

namespace StoreKeeper\ApiWrapper;

use StoreKeeper\ApiWrapper\Wrapper\WrapperInterface;

class ApiWrapper extends ActionWrapper implements ApiWrapperInterface
{
    protected ?Auth $auth = null;

    public function __construct(?WrapperInterface $wrapper = null, ?Auth $auth = null)
    {
        parent::__construct($wrapper);
        if (!empty($auth)) {
            $this->setAuth($auth);
        }
    }

    public function setAuth(Auth $auth): void
    {
        $this->auth = $auth;
    }

    public function getAuth(): Auth
    {
        return $this->auth;
    }

    public function callFunction(string $module_name, string $name, array $params = [], ?Auth $auth = null): mixed
    {
        if (is_null($auth)) {
            $auth = $this->auth;
        }
        if (is_null($auth)) {
            throw new \LogicException('Auth cannot be empty for the call');
        }
        $auth->revalidate();

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

    public function getModule(string $module_name, ?Auth $auth = null): ModuleApiWrapperInterface
    {
        return new ModuleApiWrapper($this, $module_name, $auth);
    }

    public function __get($name)
    {
        return $this->getModule($name);
    }
}
