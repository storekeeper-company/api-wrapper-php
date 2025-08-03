<?php

namespace StoreKeeper\ApiWrapperDev\Wrapper;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use StoreKeeper\ApiWrapper\Auth;
use StoreKeeper\ApiWrapper\Wrapper\AsyncWrapperInterface;

class AsyncMockAdapter extends MockAdapter implements AsyncWrapperInterface
{
    protected array $onTick = [];

    public function callAction(string $action, array $params = []): Promise
    {
        $promise = new Promise();

        $this->onTick[] = [
            $promise,
            function () use ($action, $params) {
                return parent::callAction($action, $params);
            },
        ];

        return $promise;
    }

    public function call(string $module, string $name, array $params, Auth $auth): Promise
    {
        $promise = new Promise();

        $this->onTick[] = [
            $promise,
            function () use ($module, $name, $params, $auth) {
                return parent::call($module, $name, $params, $auth);
            },
        ];

        return $promise;
    }

    public function doTheTick(): void
    {
        $onTick = $this->onTick;
        $this->onTick = [];
        /* @var $promise PromiseInterface */
        foreach ($onTick as [$promise, $callable]) {
            try {
                $result = $callable();
                $promise->resolve($result);
            } catch (\Throwable $e) {
                $promise->reject($e);
            }
        }
        Utils::queue()->run();
    }
}
