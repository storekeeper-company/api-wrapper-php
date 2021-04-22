<?php

namespace StoreKeeper\ApiWrapper;

interface ModuleApiWrapperInterface
{
    /**
     * @return string
     */
    public function getModuleName(): string;

    /**
     * @param Auth $auth
     */
    public function setAuth(Auth $auth): void;
    /**
     * @return Auth
     */
    public function getAuth(): Auth;
}