<?php

namespace StoreKeeper\ApiWrapperDev\Test\Wrapper;

use GuzzleHttp\Promise\PromiseInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\ExpectationInterface;
use PHPUnit\Framework\TestCase;
use StoreKeeper\ApiWrapper\ApiWrapper;
use StoreKeeper\ApiWrapper\Auth\AnonymousAuth;
use StoreKeeper\ApiWrapperDev\Wrapper\AsyncMockAdapter;

class AsyncMockAdapterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testRegisteredActionSuccess()
    {
        $adapter = new AsyncMockAdapter();
        $wrapper = new ApiWrapper($adapter, new AnonymousAuth(''));

        $expect_return = uniqid('ret');
        $adapter->withAction('testAction', function (ExpectationInterface $mockCall) use ($expect_return) {
            $mockCall->andReturn($expect_return);
        });

        /* @var $return PromiseInterface */
        $return = $wrapper->callAction('testAction', ['a', 'b']);
        $this->assertSame(PromiseInterface::PENDING, $return->getState(), 'state');

        $wasCalled = false;
        $return->then(
            function ($return) use ($expect_return, &$wasCalled) {
                $this->assertSame($expect_return, $return);
                $wasCalled = true;
            }
        );
        $adapter->doTheTick();

        $this->assertSame(PromiseInterface::FULFILLED, $return->getState(), 'state');
        $this->assertTrue($wasCalled, 'onFullfiled was called');
    }

    public function testRegisteredActionError()
    {
        $adapter = new AsyncMockAdapter();
        $wrapper = new ApiWrapper($adapter, new AnonymousAuth(''));

        $expect_throw = uniqid('ret');
        $adapter->withAction('testAction', function (ExpectationInterface $mockCall) use ($expect_throw) {
            $mockCall->andThrow(new \Exception($expect_throw));
        });

        /* @var $return PromiseInterface */
        $return = $wrapper->callAction('testAction', ['a', 'b']);
        $this->assertSame(PromiseInterface::PENDING, $return->getState(), 'state');

        $wasCalled = false;
        $return->otherwise(
            function (\Throwable $return) use ($expect_throw, &$wasCalled) {
                $this->assertSame($expect_throw, $return->getMessage());
                $wasCalled = true;
            }
        );
        $adapter->doTheTick();
        $this->assertSame(PromiseInterface::REJECTED, $return->getState(), 'state');
        $this->assertTrue($wasCalled, 'otherwise was called');
    }
}
