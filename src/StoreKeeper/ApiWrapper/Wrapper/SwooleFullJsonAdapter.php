<?php
namespace StoreKeeper\ApiWrapper\Wrapper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Swoole\Server;
use StoreKeeper\ApiWrapper\Exception\GeneralException;

/**
 * Class AsyncFullJsonAdapter
 * @package StoreKeeper\ApiWrapper\Wrapper
 */
class SwooleFullJsonAdapter extends AsyncFullJsonAdapter {
    /**
     * @var Server
     */
    protected $serv;

    public function __construct(Server $swoole, $server = null, array $connection_options = [])
    {
        $this->serv = $swoole;
        parent::__construct($server, $connection_options);
    }
    /**
     * @param $call
     */
    protected function postProcessCall(PromiseInterface $call)
    {
        parent::postProcessCall($call);

        // set timer to check response later
        $this->serv->tick(500, function ($timer_id) use ($call){
            $this->doTheTick();
            if ($call->getState() != PromiseInterface::PENDING ){
                $this->serv->clearTimer($timer_id);
            }
        });
    }
    function __toString(){
        return 'SwooleFullJsonAdapter('.$this->server.')';
    }
}

