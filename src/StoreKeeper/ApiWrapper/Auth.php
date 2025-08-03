<?php

namespace StoreKeeper\ApiWrapper;

class Auth
{
    protected array $auth = [];

    protected array $extra = [];

    protected ?\DateTimeInterface $authenticatedAt = null;
    /**
     * @var callable|null
     */
    protected $refreshCallback;

    public function __construct(?array $auth = null, ?array $extra = null)
    {
        if (!empty($auth)) {
            $this->setAuth($auth);
        }
        if (!empty($extra)) {
            $this->setExtra($extra);
        }
        $this->setAuthenticatedAt();
    }

    public function getAuthenticatedAt(): ?\DateTimeInterface
    {
        return $this->authenticatedAt;
    }

    public function setAuthenticatedAt(?\DateTimeInterface $authenticatedAt = null): void
    {
        $this->authenticatedAt = $authenticatedAt ?? new \DateTime();
    }

    public function hasRefreshCallback(): bool
    {
        return !is_null($this->refreshCallback);
    }

    public function setRefreshCallback(?callable $refreshCallback): void
    {
        $this->refreshCallback = $refreshCallback;
    }

    /**
     * @return bool if new auth was set
     */
    public function revalidate(): bool
    {
        if ($this->hasRefreshCallback()) {
            $fn = $this->refreshCallback;
            $result = $fn($this);

            return !empty($result);
        }

        return false;
    }

    /**
     * if account login data is set.
     */
    final public function isAccountSet(): bool
    {
        return array_key_exists('account', $this->auth)
            && !empty($this->auth['account']);
    }

    /**
     * if login method data is set.
     */
    final public function isLoginMethodSet(): bool
    {
        return array_key_exists('mode', $this->auth)
            && !empty($this->auth['mode']);
    }

    public function setExtra(array $extra): void
    {
        $this->extra = $extra;
    }

    public function addExtra(string $name, mixed $data): void
    {
        $this->extra[$name] = $data;
    }

    public function getExtra(): array
    {
        return $this->extra;
    }

    /**
     * sets user.
     */
    public function setAnonymous(): void
    {
        $this->auth['user'] = 'anonymous';
        $this->auth['rights'] = 'anonymous';
        $this->auth['mode'] = 'none';
    }

    /**
     * sets user.
     *
     * @param string $user user login
     */
    public function setUser($user): void
    {
        $this->auth['user'] = $user;
        $this->auth['rights'] = 'user';
    }

    /**
     * sets subuser.
     *
     * @param string $subaccount subaccount name
     * @param string $user       subuser login
     */
    public function setSubuser($subaccount, $user): void
    {
        $this->auth['user'] = $user;
        $this->auth['subaccount'] = $subaccount;
        $this->auth['rights'] = 'subuser';
    }

    /**
     * @param string $name account to use
     */
    public function setAccount($name): void
    {
        $this->auth['account'] = $name;
    }

    public function getAccount(): ?string
    {
        return $this->auth['account'];
    }

    /**
     * authorisation to hash.
     *
     * @param string $hash hash got before
     */
    public function setHash($hash): void
    {
        $this->auth['hash'] = $hash;
        $this->auth['mode'] = 'hash';
    }

    /**
     * authorisation to password.
     *
     * @param string $password hash got before
     */
    public function setPassword($password): void
    {
        $this->auth['password'] = $password;
        $this->auth['mode'] = 'password';
    }

    /**
     * authorisation to apikey.
     *
     * @param string $apikey hash got before
     */
    public function setApiKey($apikey): void
    {
        $this->auth['apikey'] = $apikey;
        $this->auth['mode'] = 'apikey';
    }

    /**
     * @param string $name
     */
    public function setClientName($name = 'Php Wrappers'): void
    {
        $this->auth['client_name'] = $name;
    }

    /**
     * @return mixed
     */
    public function getClientName(): ?string
    {
        return $this->auth['client_name'];
    }

    /**
     * @param string $ip
     */
    public function setClientIp($ip = null): void
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
    public function getClientIp(): ?string
    {
        if (!array_key_exists('user_ip', $this->auth)) {
            $this->setClientIp();
        }

        return $this->auth['user_ip'];
    }

    public function isValid(): bool
    {
        return $this->isAccountSet() && $this->isLoginMethodSet();
    }

    public function setAuth(array $auth): void
    {
        $this->auth = $auth;
    }

    public function getAuth(): array
    {
        return $this->auth;
    }
}
