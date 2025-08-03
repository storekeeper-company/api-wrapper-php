<?php

namespace StoreKeeper\ApiWrapper\Wrapper;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
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

    protected ?Client $client = null;

    protected string $server = '';

    protected LoggerInterface $logger;

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setServer(string $server, array $options = []): void
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

    protected function makeActionPath(string $action): string
    {
        return '/?action='.urlencode($action).'&api=fulljson';
    }

    protected function makeApiRequestPath(string $module, string $function)
    {
        return $this->makeActionPath('request')
        .'&module='.urlencode($module).'&function='.urlencode($function);
    }

    public function call(string $module, string $name, array $params, Auth $auth): mixed
    {
        if (!$auth->isValid()) {
            throw new \LogicException('Auth is not properly setup');
        }
        $url = $this->makeApiRequestPath($module, $name);
        $params = [
            'auth' => $auth->getAuth(),
            'params' => $params,
        ];

        return $this->callUrl($url, $params, "$module::$name");
    }

    public function callUrl(string $url, array $params, string $name): mixed
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


    public function callAction(string $action, array $params = []): mixed
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
