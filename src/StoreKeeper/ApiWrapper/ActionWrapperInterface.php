<?php

namespace StoreKeeper\ApiWrapper;


use StoreKeeper\ApiWrapper\Wrapper\WrapperInterface;

/**
 * Class ApiWrapper
 * @package StoreKeeper\ApiWrapper
 */
interface ActionWrapperInterface
{
    /**
     * @param WrapperInterface $wrapper
     */
    function setWrapper(WrapperInterface $wrapper);

    /**
     * @return \StoreKeeper\ApiWrapper\Wrapper\WrapperInterface
     */
    public function getWrapper();

    /**
     * @param $action
     * @param array $params
     *
     * @return mixed
     */
    function callAction($action, array $params = array());
}
