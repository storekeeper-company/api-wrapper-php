<?php

namespace StoreKeeper\ApiWrapper;

use StoreKeeper\ApiWrapper\Wrapper\WrapperInterface;

/**
 * Class ApiWrapper.
 */
interface ActionWrapperInterface
{
    public function setWrapper(WrapperInterface $wrapper);

    /**
     * @return \StoreKeeper\ApiWrapper\Wrapper\WrapperInterface
     */
    public function getWrapper();

    /**
     * @param $action
     *
     * @return mixed
     */
    public function callAction($action, array $params = []);
}
