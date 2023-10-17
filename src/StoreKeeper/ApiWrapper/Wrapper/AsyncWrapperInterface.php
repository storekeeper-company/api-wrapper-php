<?php

namespace StoreKeeper\ApiWrapper\Wrapper;

interface AsyncWrapperInterface extends WrapperInterface
{
    public function doTheTick();
}
