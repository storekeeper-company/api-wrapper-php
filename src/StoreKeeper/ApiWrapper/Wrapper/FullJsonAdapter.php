<?php

namespace StoreKeeper\ApiWrapper\Wrapper;

use GuzzleHttp\Client;
use StoreKeeper\ApiWrapper\Auth;
use StoreKeeper\ApiWrapper\Exception\GeneralException;

class FullJsonAdapter implements WrapperInterface
{
    public function __construct($server = null, array $connection_options = [])
    {
        if (!empty($server)) {
            $this->setServer($server, $connection_options);
        }
    }

    /**
     * @var Client
     */
    protected $client;
    /**
     * @var string
     */
    protected $server;

    /**
     * @var
     */
    protected $logger;

    /**
     * @param $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * sets server to connect to.
     *
     * @param string $server
     */
    public function setServer($server, array $options = [])
    {
        $this->server = $server;
        $this->client = new Client($options + $this->getDefaultConfig($server));
    }

    public function getServer(): string
    {
        return $this->server;
    }

    public function getResourceUrl(): string
    {
        return $this->getServer();
    }

    /**
     * @param $module
     * @param $function
     *
     * @return string
     */
    protected function makeActionPath($action)
    {
        return '/?action='.urlencode($action).'&api=fulljson';
    }

    /**
     * @param $module
     * @param $function
     *
     * @return string
     */
    protected function makeApiRequestPath($module, $function)
    {
        return $this->makeActionPath('request')
        .'&module='.urlencode($module).'&function='.urlencode($function);
    }

    /**
     * @param $module
     * @param $function
     * @param $params
     * @param $auth
     *
     * @return mixed
     */
    public function call($module, $function, $params, Auth $auth)
    {
        if (!$auth->isValid()) {
            throw new \LogicException('Auth is not properly setup');
        }
        $url = $this->makeApiRequestPath($module, $function);
        $params = [
            'auth' => $auth->getAuth(),
            'params' => $params,
        ];

        return $this->callUrl($url, $params, "$module::$function");
    }

    /**
     * @param $action
     * @param $params
     *
     * @return mixed
     */
    public function callUrl($url, $params, $name)
    {
        if (!empty($this->logger)) {
            $time_start = microtime(true);
        }

        if (is_null($this->client)) {
            throw new \LogicException('Server is not set');
        }
        $options = [
            'json' => $params,
        ];

        $request = $this->client->post($url, $options);
        $res = (string) $request->getBody();
        $response_body = json_decode($res, true);

        if (!empty($this->logger)) {
            $time = round((microtime(true) - $time_start) * 1000);
            $this->logger->debug(
                "StoreKeeperWrapper: Call to $name [{$time}ms]"
            );
        }
        if (!$response_body['success']) {
            throw GeneralException::buildFromBody($response_body);
        }

        return $response_body['response'];
    }

    /**
     * @param $action
     * @param $params
     *
     * @return mixed
     */
    public function callAction($action, $params)
    {
        return $this->callUrl($this->makeActionPath($action), $params, "Action($action)");
    }

    public function __toString()
    {
        return 'FullJsonAdapter('.$this->server.')';
    }

    protected function getDefaultConfig(string $server): array
    {
        $config = [
            'timeout' => 30,
        ];

        if (defined(Client::class.'::VERSION') // < 7.0
            && \version_compare(Client::VERSION, '6.0', '<')
        ) {
            // 5.*
            $config['base_url'] = $server;
        } else {
            $config['base_uri'] = $server;
        }

        return $config;
    }
}
