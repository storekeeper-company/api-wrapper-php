<?php

namespace StoreKeeper\ApiWrapper\Exception;

class ConnectionException extends GeneralException
{
    const READ_BODY_ERROR = 400;
    const READ_HEADERS_ERROR = 401;
    const CONNECTION_OPEN_ERROR = 500;
    const REQUEST_SEND_ERROR = 501;
    /**
     * exception class.
     *
     * @var string
     */
    protected $exc_class = 'Connection';
}
