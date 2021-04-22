<?php

namespace StoreKeeper\ApiWrapperDev\Wrapper;

use Mockery\ExpectationInterface;
use Mockery\MockInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use StoreKeeper\ApiWrapper\ActionWrapperInterface;
use StoreKeeper\ApiWrapper\Auth;
use StoreKeeper\ApiWrapper\ModuleApiWrapper;
use StoreKeeper\ApiWrapper\ModuleApiWrapperInterface;
use StoreKeeper\ApiWrapper\Wrapper\WrapperInterface;
use StoreKeeper\ApiWrapperDev\DumpFile;
use StoreKeeper\ApiWrapperDev\MockAction;
use StoreKeeper\ApiWrapperDev\MockModuleApiWrapperFactory;

class MockAdapter implements WrapperInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    /**
     * @var
     */
    protected $resource_url;

    public function getServer(): string
    {
        return 'MOCK';
    }

    public function getResourceUrl(): string
    {
        if (empty($this->resource_url)) {
            return $this->getServer();
        }

        return $this->resource_url;
    }

    public function setResourceUrl(?string $resource_url): void
    {
        $this->resource_url = $resource_url;
    }

    /**
     * @var ModuleApiWrapperInterface[]
     */
    protected $registered_modules = [];
    /**
     * @var MockAction[]
     */
    protected $registered_actions = [];
    /**
     * @var
     */
    protected $returns = [];
    /**
     * @var
     */
    protected $used_return_keys = [];

    public function __construct()
    {
        $this->setLogger(new NullLogger());
    }

    public function setServer($server, array $options = [])
    {
        $this->logger->debug('setServer', [
            'server' => $server,
            'options' => $options,
        ]);
    }

    public function callAction($action, $params)
    {
        if (!array_key_exists($action, $this->registered_actions)) {
            throw new \Exception("Action $action is not registered");
        }
        $call = $this->registered_actions[$action];

        return $call->onCall($params);
    }

    /**
     * @throws \Exception
     */
    public function registeredAction(MockAction $action, bool $overwrite = false)
    {
        $name = $action->getName();
        if (array_key_exists($name, $this->registered_actions) && !$overwrite) {
            throw new \Exception("Action $name is already set up");
        }
        $this->registered_actions[$name] = $action;
        $this->logger->debug('registeredAction', [
            'name' => $name,
        ]);
    }

    /**
     * @param $name
     *
     * @throws \Exception
     */
    public function withAction($name, callable $call, bool $overwrite = false)
    {
        $mock = new MockAction($name);
        $mock->setMock($call);
        $this->registeredAction($mock, $overwrite);
    }

    /**
     * @param $module
     * @param $name
     * @param $params
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function call($module, $name, $params, Auth $auth)
    {
        if (!array_key_exists($module, $this->registered_modules)) {
            throw new \Exception("Action $module is not registered");
        }
        $obj = $this->registered_modules[$module];
        $moduleAuth = $obj->getAuth();
        if (is_null($moduleAuth)) {
            // only use the auth in case ti`s not already set on the modules
            // if done this way make it possible to still make calls to real api
            // using real credentials set on ModuleApiWrapper
            $obj->setAuth($auth);
        }

        return $obj->$name($params);
    }

    /**
     * @throws \Exception
     */
    public function registeredModuleApiWrapper(ModuleApiWrapperInterface $module, bool $overwrite = false)
    {
        $name = $module->getModuleName();
        if (array_key_exists($name, $this->registered_modules) && !$overwrite) {
            throw new \Exception("Module $name is already set up");
        }
        $this->registered_modules[$name] = $module;
        $this->logger->debug('registeredModuleApiWrapper', [
            'name' => $name,
        ]);
    }

    /**
     * @param $name
     *
     * @throws \Exception
     */
    public function withModule($name, callable $call)
    {
        if (array_key_exists($name, $this->registered_modules)) {
            $module = $this->registered_modules[$name];
        } else {
            $module = MockModuleApiWrapperFactory::buildMock($name);
            $this->registeredModuleApiWrapper($module);
        }
        $call($module);
    }

    /**
     * @param $name
     * @param callable $call
     *
     * @throws \Exception
     */
    public function withOriginalModule(ActionWrapperInterface $wrapper, $name, callable $call = null)
    {
        $module = \Mockery::mock(new ModuleApiWrapper($wrapper, $name, $wrapper->getAuth()));
        $this->registeredModuleApiWrapper($module);
        if (!empty($call)) {
            $call($module);
        }
    }

    /**
     * @throws \Exception
     */
    public function registerDumpFile(string $filepath, bool $matchParams = false, DumpFile\Reader $reader = null)
    {
        if (is_null($reader)) {
            $reader = new DumpFile\Reader();
        }
        $file = $reader->read($filepath);
        $type = $file->getType();

        $this->addReturn($matchParams, $file);
        if (DumpFile::ACTION_TYPE === $type) {
            $this->withAction($file->getActionName(), function (ExpectationInterface $mockCall) use ($file) {
                $mockCall->andReturnUsing(function ($args) use ($file) {
                    return $this->returnFn($args, $file);
                });
            });
        } elseif (DumpFile::MODULE_TYPE === $type) {
            $this->withModule($file->getModuleName(), function (MockInterface $module) use ($file) {
                $module->shouldReceive($file->getModuleFunction())
                       ->andReturnUsing(function ($args) use ($file) {
                           return $this->returnFn($args, $file);
                       });
            });
        } else {
            throw new \RuntimeException("Unknown type $type for $filepath");
        }
    }

    private function addReturn(bool $matchParams, DumpFile $file)
    {
        $return = $file->getReturn();
        $key = $matchParams ?
            $file->getReturnMatchKeyWithParams() :
            $file->getReturnMatchKey();
        $this->returns[$key] = $return;
    }

    /**
     * @param $args
     *
     * @return mixed
     *
     * @throws \Exception
     */
    private function returnFn($args, DumpFile $file)
    {
        $key = $file->getReturnMatchKeyWithParams($args);
        if (!array_key_exists($key, $this->returns)) {
            $key = $file->getReturnMatchKey();
            if (!array_key_exists($key, $this->returns)) {
                throw new \Exception("No return registered for key $key. Args: ".json_encode($args));
            }
        }
        $this->used_return_keys[] = $key;

        return $this->returns[$key];
    }

    /**
     * @return mixed
     */
    public function getUsedReturns()
    {
        return $this->used_return_keys;
    }

    /**
     * @throws \Exception
     */
    public function registerDumpFiles(
        array $filepaths,
        string $path_prefix = '',
        bool $matchParams = false,
        DumpFile\Reader $reader = null)
    {
        foreach ($filepaths as $filepath) {
            if (!empty($path_prefix)) {
                $filepath = $path_prefix.DIRECTORY_SEPARATOR.$filepath;
            }
            $this->registerDumpFile($filepath, $matchParams, $reader);
        }
    }

    public function __toString()
    {
        return 'MockAdapter';
    }
}
