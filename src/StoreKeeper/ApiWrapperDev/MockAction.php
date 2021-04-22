<?php

namespace StoreKeeper\ApiWrapperDev;

use Mockery\ExpectationInterface;
use Mockery\MockInterface;

class MockAction
{
    /**
     * @var string
     */
    protected $name;
    /**
     * @var MockInterface
     */
    protected $mock;

    /**
     * MockAction constructor.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param callable $call
     */
    public function onCall($params)
    {
        if (empty($this->mock)) {
            $this->setMock();
        }

        return $this->mock->onCall($params);
    }

    public function setMock(callable $builder = null): MockAction
    {
        if (empty($builder)) {
            $builder = function (ExpectationInterface $mockCall) {
                $mockCall->andReturn(null);
            };
        }
        $this->mock = \Mockery::mock();
        $mockCall = $this->mock->shouldReceive('onCall');
        $builder($mockCall);

        return $this;
    }
}
