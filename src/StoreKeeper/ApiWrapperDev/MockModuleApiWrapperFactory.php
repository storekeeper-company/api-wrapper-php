<?php

namespace StoreKeeper\ApiWrapperDev;

use Mockery\MockInterface;
use StoreKeeper\ApiWrapper\Auth\AnonymousAuth;
use StoreKeeper\ApiWrapper\ModuleApiWrapperInterface;

class MockModuleApiWrapperFactory
{
    public static function buildMock(string $name): MockInterface
    {
        $mock = \Mockery::mock(ModuleApiWrapperInterface::class);
        $mock->shouldReceive('getModuleName')->andReturn($name);
        $mock->shouldReceive('setAuth')->andReturn(null);
        $mock->shouldReceive('getAuth')->andReturn(new AnonymousAuth(''));

        return $mock;
    }
}
