<?php

namespace StoreKeeper\ApiWrapper\Wrapper;

use StoreKeeper\ApiWrapper\Auth;

interface WrapperInterface extends \Stringable
{
    /**
     * sets server to connect to.
     */
    public function setServer(string $server, array $options = []): void;

    public function getServer(): string;

    public function getResourceUrl(): string;

    public function call(string $module, string $name, array $params, Auth $auth): mixed;

    public function callAction(string $action, array $params = []): mixed;
}
