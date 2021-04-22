<?php

namespace StoreKeeper\ApiWrapper;

class ModuleApiWrapper implements ModuleApiWrapperInterface
{
    /**
     * @var ActionWrapperInterface
     */
    protected $api_wrapper;
    /**
     * @var string
     */
    protected $module_name;
    /**
     * @var Auth
     */
    protected $auth;

    /**
     * @param $module_name
     * @param Auth $auth
     *
     * @throws \LogicException when module name is empty
     */
    public function __construct(ActionWrapperInterface $api_wrapper, $module_name, Auth $auth = null)
    {
        $this->api_wrapper = $api_wrapper;
        $module_name = trim($module_name);
        if (empty($module_name)) {
            throw new \LogicException('Module name for wrapper cannot be empty');
        }
        $this->module_name = $module_name;
        $this->auth = $auth;
    }

    public function getModuleName(): string
    {
        return $this->module_name;
    }

    public function setAuth(Auth $auth): void
    {
        $this->auth = $auth;
    }

    public function getAuth(): Auth
    {
        return $this->auth;
    }

    /**
     * @param $name
     * @param $params
     *
     * @return mixed
     */
    public function __call($name, $params)
    {
        return $this->api_wrapper->callFunction(
            $this->module_name,
            $name,
            $params,
            $this->auth
        );
    }
}
