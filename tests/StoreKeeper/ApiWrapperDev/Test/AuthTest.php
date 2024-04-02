<?php

namespace StoreKeeper\ApiWrapperDev\Test;

use PHPUnit\Framework\TestCase;
use StoreKeeper\ApiWrapper\Auth;

class AuthTest extends TestCase
{
    public function testRevalidateEmpty()
    {
        $auth = new Auth();

        $this->assertFalse($auth->revalidate());
    }

    public function testRevalidateDate()
    {
        $auth = new Auth();

        $newAuthDate = new \DateTime('2024-01-01');
        $newAuthHash = 'iqweqwpieipqwoieqwopie';
        $auth->setRevalidateCallback(
            function (Auth $auth) use ($newAuthDate, $newAuthHash) {
                $auth->setAuthenticatedAt($newAuthDate);
                $auth->setHash($newAuthHash);

                return true;
            }
        );

        $this->assertTrue($auth->revalidate(), 'revelidated');
        $this->assertEquals($newAuthDate, $auth->getAuthenticatedAt(), 'date changed');
        $this->assertEquals($newAuthHash, $auth->getAuth()['hash'], 'hash changed');
    }
}
