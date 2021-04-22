<?php

namespace StoreKeeper\ApiWrapper\Wrapper;

use StoreKeeper\ApiWrapper\Auth;

interface WrapperInterface
{
    /**
     * sets server to connect to.
     *
     * @param string $server
     *
     * @return
     */
    public function setServer($server, array $options = []);

    public function getServer(): string;

    public function getResourceUrl(): string;

    /**
     * @param $module
     * @param $name
     * @param $params
     * @param $auth
     *
     * @return mixed
     */
    public function call($module, $name, $params, Auth $auth);

    /**
     * @param $action
     * @param $params
     *
     * @return mixed
     */
    public function callAction($action, $params);

    /**
     * @return string
     */
    public function __toString();
}
