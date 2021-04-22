<?php

namespace StoreKeeper\ApiWrapper;

interface ModuleApiWrapperInterface
{
    public function getModuleName(): string;

    public function setAuth(Auth $auth): void;

    public function getAuth(): Auth;
}
