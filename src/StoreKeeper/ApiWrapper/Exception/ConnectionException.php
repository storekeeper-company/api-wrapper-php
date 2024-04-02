<?php

namespace StoreKeeper\ApiWrapper\Exception;

class ConnectionException extends GeneralException
{
    public const READ_BODY_ERROR = 400;
    public const READ_HEADERS_ERROR = 401;
    public const CONNECTION_OPEN_ERROR = 500;
    public const REQUEST_SEND_ERROR = 501;
    /**
     * exception class.
     *
     * @var string
     */
    protected $exc_class = 'Connection';
}
