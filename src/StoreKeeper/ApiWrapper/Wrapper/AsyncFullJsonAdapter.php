<?php
namespace StoreKeeper\ApiWrapper\Wrapper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use StoreKeeper\ApiWrapper\Exception\GeneralException;
/**
 * Class AsyncFullJsonAdapter
 * @package StoreKeeper\ApiWrapper\Wrapper
 */
class AsyncFullJsonAdapter extends FullJsonAdapter {
    /**
     * @var CurlMultiHandler
     */
    protected $handler;

    /**
     * sets server to connect to
     * @param string $server
     * @param array $options
     */
    function setServer($server, array $options = [])
    {
        $this->handler = new CurlMultiHandler($options + [
            'select_timeout' => 0.0001 // really small timeout to skip the blocking
        ]);
        $this->client = new Client([
            'base_uri' => $server,
            'handler' => HandlerStack::create($this->handler)
        ]);
    }
    /**
     * @param $action
     * @param $params
     * @return mixed
     */
    function callUrl( $url, $params , $name ){

        if( is_null($this->client) ){
            throw new \LogicException("Server is not set");
        }
        $time_start = microtime(true);
        $options = [
            'json' => $params,
        ];

        $promise = new Promise();
        $call = $this->client->postAsync($url,$options);
        $call->then(function (ResponseInterface $response) use($time_start, $name, $promise){
            $res = (string)$response->getBody();
            $response_body = json_decode( $res , true);

            if(!empty($this->logger)) {
                $time = round( (microtime(true) - $time_start)* 1000 );
                $this->logger->debug(
                    "StoreKeeperWrapper: Call to $name [{$time}ms]"
                );
            }
            if( !$response_body['success'] ){
                $promise->reject( GeneralException::buildFromBody($response_body) );
            }
            $promise->resolve($response_body['response'] ?? null);
        }, function (RequestException $e) use($time_start, $name, $promise){
            if(!empty($this->logger)) {
                $time = round( (microtime(true) - $time_start)* 1000 );
                $this->logger->debug(
                    "StoreKeeperWrapper: Call to $name [{$time}ms]"
                );
            }
            $promise->reject($e);
        });
        $this->postProcessCall($call);
        return $promise;
    }
    /**
     * @param PromiseInterface $call
     */
    protected function postProcessCall(PromiseInterface $call)
    {
        // tick, tick, tick to process the initial handlers
        $this->doTheTick();
        $this->doTheTick();
        $this->doTheTick();
    }
    /**
     * processes next
     */
    function doTheTick()
    {
        $this->handler->tick();
    }

    function __toString(){
        return 'AsyncFullJsonAdapter('.$this->server.')';
    }
}
