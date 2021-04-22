<?php

namespace StoreKeeper\ApiWrapper\Wrapper;

use GuzzleHttp\Promise\PromiseInterface;
use Swoole\Server;

/**
 * Class AsyncFullJsonAdapter.
 */
class SwooleFullJsonAdapter extends AsyncFullJsonAdapter
{
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
        $this->serv->tick(500, function ($timer_id) use ($call) {
            $this->doTheTick();
            if (PromiseInterface::PENDING != $call->getState()) {
                $this->serv->clearTimer($timer_id);
            }
        });
    }

    public function __toString()
    {
        return 'SwooleFullJsonAdapter('.$this->server.')';
    }
}
