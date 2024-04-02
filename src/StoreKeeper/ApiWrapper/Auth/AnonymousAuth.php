<?php

namespace StoreKeeper\ApiWrapper\Auth;

use StoreKeeper\ApiWrapper\Auth;

class AnonymousAuth extends Auth
{
    public function __construct($account_name)
    {
        parent::__construct();
        $this->setAccount($account_name);
        $this->setAnonymous();
    }
}
