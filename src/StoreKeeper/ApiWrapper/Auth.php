<?php

namespace StoreKeeper\ApiWrapper;

class Auth
{
    /**
     * @var array
     */
    protected $auth = [];
    /**
     * @var array
     */
    protected $extra = [];

    /**
     * @param array $auth
     */
    public function __construct(array $auth = null, array $extra = null)
    {
        if (!empty($auth)) {
            $this->setAuth($auth);
        }
        if (!empty($extra)) {
            $this->setExtra($extra);
        }
    }

    /**
     * if account login data is set.
     */
    final public function isAccountSet()
    {
        return array_key_exists('account', $this->auth)
            && !empty($this->auth['account']);
    }

    /**
     * if login method data is set.
     */
    final public function isLoginMethodSet()
    {
        return array_key_exists('mode', $this->auth)
            && !empty($this->auth['mode']);
    }

    public function setExtra(array $extra)
    {
        $this->extra = $extra;
    }

    /**
     * @param array $extra
     */
    public function addExtra($name, $data)
    {
        $this->extra[$name] = $data;
    }

    /**
     * @return array
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * sets user.
     *
     * @param string $account account name
     * @param string $user    user login
     */
    public function setAnonymous()
    {
        $this->auth['user'] = 'anonymous';
        $this->auth['rights'] = 'anonymous';
        $this->auth['mode'] = 'none';
    }

    /**
     * sets user.
     *
     * @param string $account account name
     * @param string $user    user login
     */
    public function setUser($user)
    {
        $this->auth['user'] = $user;
        $this->auth['rights'] = 'user';
    }

    /**
     * sets subuser.
     *
     * @param string $account    account name
     * @param string $subaccount subaccount name
     * @param string $user       subuser login
     */
    public function setSubuser($subaccount, $user)
    {
        $this->auth['user'] = $user;
        $this->auth['subaccount'] = $subaccount;
        $this->auth['rights'] = 'subuser';
    }

    /**
     * @param string $name account to use
     */
    public function setAccount($name)
    {
        $this->auth['account'] = $name;
    }

    public function getAccount()
    {
        return $this->auth['account'];
    }

    /**
     * authorisation to hash.
     *
     * @param string $hash hash got before
     */
    public function setHash($hash)
    {
        $this->auth['hash'] = $hash;
        $this->auth['mode'] = 'hash';
    }

    /**
     * authorisation to password.
     *
     * @param string $password hash got before
     */
    public function setPassword($password)
    {
        $this->auth['password'] = $password;
        $this->auth['mode'] = 'password';
    }

    /**
     * authorisation to apikey.
     *
     * @param string $apikey hash got before
     */
    public function setApiKey($apikey)
    {
        $this->auth['apikey'] = $apikey;
        $this->auth['mode'] = 'apikey';
    }

    /**
     * @param string $name
     */
    public function setClientName($name = 'Php Wrappers')
    {
        $this->auth['client_name'] = $name;
    }

    /**
     * @return mixed
     */
    public function getClientName()
    {
        return $this->auth['client_name'];
    }

    /**
     * @param string $ip
     */
    public function setClientIp($ip = null)
    {
        if (empty($ip) && array_key_exists('REMOTE_ADDR', $_SERVER)) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        if (empty($ip) && array_key_exists('SERVER_ADDR', $_SERVER)) {
            $ip = $_SERVER['SERVER_ADDR '];
        }
        if (empty($ip)) {
            $ip = '127.0.0.1';
        }
        $this->auth['user_ip'] = $ip;
    }

    /**
     * @return mixed
     */
    public function getClientIp()
    {
        if (!array_key_exists('user_ip', $this->auth)) {
            $this->setClientIp();
        }

        return $this->auth['user_ip'];
    }

    /**
     * @param array $auth
     */
    public function isValid()
    {
        return $this->isAccountSet() && $this->isLoginMethodSet();
    }

    public function setAuth(array $auth)
    {
        $this->auth = $auth;
    }

    /**
     * @return array
     */
    public function getAuth()
    {
        return $this->auth;
    }
}
