<?php

namespace StoreKeeper\ApiWrapper;

/**
 * Class ApiWrapper.
 */
interface ApiWrapperInterface extends ActionWrapperInterface
{
    public function setAuth(Auth $auth);

    public function getAuth(): Auth;

    public function callFunction(string $module_name, string $name, array $params = [], ?Auth $auth = null): mixed;

    public function getModule(string $module_name, ?Auth $auth = null): ModuleApiWrapperInterface;
}
