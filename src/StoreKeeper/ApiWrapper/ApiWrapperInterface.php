<?php

namespace StoreKeeper\ApiWrapper;


/**
 * Class ApiWrapper
 * @package StoreKeeper\ApiWrapper
 */
interface ApiWrapperInterface extends ActionWrapperInterface
{
    /**
     * @param Auth $auth
     */
    function setAuth(Auth $auth);

    /**
     * @return \StoreKeeper\ApiWrapper\Auth
     */
    public function getAuth(): \StoreKeeper\ApiWrapper\Auth;

    /**
     * @param $module_name
     * @param $name
     * @param $params
     * @param Auth $auth
     *
     * @return mixed
     */
    function callFunction($module_name, $name, array $params = array(), Auth $auth = null);

    /**
     * @param $module_name
     * @param Auth $auth
     *
     * @return ModuleApiWrapperInterface
     */
    function getModule($module_name, Auth $auth = null): ModuleApiWrapperInterface;
}
