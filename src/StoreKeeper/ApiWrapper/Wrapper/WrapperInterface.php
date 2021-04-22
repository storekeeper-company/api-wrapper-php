<?php
namespace StoreKeeper\ApiWrapper\Wrapper;
use StoreKeeper\ApiWrapper\Auth;
use StoreKeeper\ApiWrapper\Exception\GeneralException;


interface WrapperInterface {
    /**
     * sets server to connect to
     * @param string $server
     * @param array $options
     * @return
     */
    function setServer($server, array $options = []);
    /**
     * @return string
     */
    public function getServer(): string;
    /**
     * @return string
     */
    public function getResourceUrl(): string;
    /**
     * @param $module
     * @param $name
     * @param $params
     * @param $auth
     * @return mixed
     */
    function call($module, $name, $params, Auth $auth);
    /**
     * @param $action
     * @param $params
     * @return mixed
     */
    function callAction($action, $params);

    /**
     * @return string
     */
    function __toString();
}

