<?php
namespace StoreKeeper\ApiWrapper;

class Auth {
    /**
     * @var array
     */
    protected $auth = array();
    /**
     * @var array
     */
    protected $extra = array();
    /**
     * @param array $auth
     */
    function __construct(array $auth = null,array $extra = null) {
        if( !empty( $auth )){
            $this->setAuth($auth);
        }
        if( !empty( $extra )){
            $this->setExtra($extra);
        }
    }
    /**
     * if account login data is set
     */
    final function isAccountSet(){
        return array_key_exists('account', $this->auth)
            && !empty($this->auth['account'] );
    }

    /**
     * if login method data is set
     */
    final function isLoginMethodSet(){
        return array_key_exists('mode', $this->auth)
            && !empty($this->auth['mode'] );
    }
    /**
     * @param array $extra
     */
    public function setExtra(array $extra)
    {
        $this->extra = $extra;
    }
    /**
     * @param array $extra
     */
    public function addExtra($name , $data)
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
     * sets user
     * @param string $account account name
     * @param string $user user login
     */
    function setAnonymous() {
        $this->auth ['user'] = 'anonymous';
        $this->auth ['rights'] = 'anonymous';
        $this->auth ['mode'] = 'none';
    }
    /**
     * sets user
     * @param string $account account name
     * @param string $user user login
     */
    function setUser( $user) {
        $this->auth ['user'] = $user;
        $this->auth ['rights'] = 'user';
    }
    /**
     * sets subuser
     * @param string $account account name
     * @param string $subaccount subaccount name
     * @param string $user subuser login
     */
    function setSubuser( $subaccount, $user) {
        $this->auth ['user'] = $user;
        $this->auth ['subaccount'] = $subaccount;
        $this->auth ['rights'] = 'subuser';
    }

    /**
     * @param string $name account to use
     */
    function setAccount($name) {
        $this->auth ['account'] = $name;
    }
    function getAccount() {
        return $this->auth ['account'];
    }
    /**
     * authorisation to hash
     * @param string $hash hash got before
     */
    function setHash($hash) {
        $this->auth ['hash'] = $hash;
        $this->auth ['mode'] = 'hash';
    }
    /**
     * authorisation to password
     * @param string $password hash got before
     */
    function setPassword($password) {
        $this->auth ['password'] = $password;
        $this->auth ['mode'] = 'password';
    }
    /**
     * authorisation to apikey
     * @param string $apikey hash got before
     */
    function setApiKey($apikey) {
        $this->auth ['apikey'] = $apikey;
        $this->auth ['mode'] = 'apikey';
    }
    /**
     * @param string $name
     */
    function setClientName($name = 'Php Wrappers') {
        $this->auth ['client_name'] = $name;
    }

    /**
     * @return mixed
     */
    function getClientName() {
        return $this->auth ['client_name'];
    }
    /**
     * @param string $ip
     */
    function setClientIp( $ip = null ) {
        if( empty($ip) && array_key_exists('REMOTE_ADDR', $_SERVER))
            $ip = $_SERVER ['REMOTE_ADDR'];
        if( empty($ip) && array_key_exists('SERVER_ADDR', $_SERVER))
            $ip = $_SERVER ['SERVER_ADDR '];
        if( empty($ip))
            $ip = '127.0.0.1';
        $this->auth ['user_ip'] = $ip;
    }
    /**
     * @return mixed
     */
    function getClientIp() {
        if( !array_key_exists('user_ip', $this->auth)){
            $this->setClientIp();
        }
        return $this->auth ['user_ip'];
    }
    /**
     * @param array $auth
     */
    function isValid() {
        return $this->isAccountSet() && $this->isLoginMethodSet();
    }
    /**
     * @param array $auth
     */
    function setAuth(array $auth) {
        $this->auth = $auth;
    }
    /**
     * @return array
     */
    function getAuth() {
        return $this->auth;
    }

} 