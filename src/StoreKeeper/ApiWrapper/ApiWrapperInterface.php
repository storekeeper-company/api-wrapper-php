<?php

namespace StoreKeeper\ApiWrapper;

/**
 * Class ApiWrapper.
 */
interface ApiWrapperInterface extends ActionWrapperInterface
{
    public function setAuth(Auth $auth);

    /**
     * @return \StoreKeeper\ApiWrapper\Auth
     */
    public function getAuth(): Auth;

    /**
     * @param $module_name
     * @param $name
     * @param $params
     * @param Auth $auth
     *
     * @return mixed
     */
    public function callFunction($module_name, $name, array $params = [], Auth $auth = null);

    /**
     * @param $module_name
     * @param Auth $auth
     */
    public function getModule($module_name, Auth $auth = null): ModuleApiWrapperInterface;
}
