<?php

namespace StoreKeeper\ApiWrapper;

use StoreKeeper\ApiWrapper\Wrapper\WrapperInterface;

/**
 * Class ApiWrapper.
 */
interface ActionWrapperInterface
{
    public function setWrapper(WrapperInterface $wrapper);

    public function getWrapper(): WrapperInterface;

    public function callAction(string $action, array $params = []): mixed;
}
