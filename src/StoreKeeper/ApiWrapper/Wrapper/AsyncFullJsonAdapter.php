<?php

namespace StoreKeeper\ApiWrapper\Wrapper;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\ResponseInterface;
use StoreKeeper\ApiWrapper\Exception\GeneralException;

/**
 * Class AsyncFullJsonAdapter.
 */
class AsyncFullJsonAdapter extends FullJsonAdapter
{
    /**
     * @var CurlMultiHandler
     */
    protected $handler;

    /**
     * sets server to connect to.
     *
     * @param string $server
     */
    public function setServer($server, array $options = [])
    {
        $this->handler = new CurlMultiHandler(
            $options + [
                'select_timeout' => 0.001, // really small timeout to skip the blocking
            ]
        );
        $this->client = new Client(
            [
                'base_uri' => $server,
                'handler' => HandlerStack::create($this->handler),
            ]
        );
    }

    /**
     * @param $action
     * @param $params
     *
     * @return mixed
     */
    public function callUrl($url, $params, $name)
    {
        if (is_null($this->client)) {
            throw new \LogicException('Server is not set');
        }
        $time_start = microtime(true);
        $options = [
            'json' => $params,
        ];

        $call = $this->client->postAsync($url, $options);

        return $call->then(
            function (ResponseInterface $response) use ($time_start, $name) {
                $res = (string) $response->getBody();
                $response_body = json_decode($res, true);

                if (!empty($this->logger)) {
                    $time = round((microtime(true) - $time_start) * 1000);
                    $this->logger->debug("StoreKeeperWrapper: Call to $name [{$time}ms]");
                }
                if (!$response_body['success']) {
                    throw GeneralException::buildFromBody($response_body);
                }
                $this->logger->debug("Call success $name");

                return $response_body['response'] ?? null;
            },
            function (\Throwable $e) use ($time_start, $name) {
                if (!empty($this->logger)) {
                    $time = round((microtime(true) - $time_start) * 1000);
                    $this->logger->debug("StoreKeeperWrapper: Call to $name [{$time}ms]");
                }
                $this->logger->debug("Call error $name", [
                    'code' => $e->getCode(),
                    'trace' => $e->getTraceAsString(),
                    'line' => $e->getLine(),
                    'mes' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'class' => get_class($e),
                ]);
                throw $e;
            }
        );
    }

    /**
     * processes next.
     */
    public function doTheTick()
    {
        $this->handler->tick();
    }

    public function __toString()
    {
        return 'AsyncFullJsonAdapter('.$this->server.')';
    }
}
