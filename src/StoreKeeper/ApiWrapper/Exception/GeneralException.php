<?php

namespace StoreKeeper\ApiWrapper\Exception;

class GeneralException extends \RuntimeException
{
    /**
     * @var string
     */
    const error_not_found_text = 'GENERAL_ERROR';
    /**
     * exception class
     * should be overwritten by derived classes.
     *
     * @var string
     */
    protected $exc_class = 'General';
    /**
     * @var string
     */
    protected $api_exception_class;
    /**
     * @var string
     */
    protected $ref = null;
    /**
     * @var string
     */
    protected $external_trace = '';

    /**
     * template to construnct the exception php class from class.
     *
     * @var string
     */
    const class_name_tpl = 'StoreKeeper\\ApiWrapper\\Exception\\%sException';

    /**
     * @param string $class error class
     * @param string $error message
     * @param int    $code  code
     *
     * @deprecated since 3.0
     */
    public static function build($class, $error, $code = 0)
    {
        trigger_error(__CLASS__.'.'.__FUNCTION__.' is depraceted use '.
            __CLASS__.'.buildFromBody or __contruct.', E_DEPRECATED);
    }

    /**
     * @param int        $code
     * @param string     $message
     * @param string     $ref
     * @param string     $external_trace_as_string
     * @param \Exception $previous
     *
     * @since 2.9
     */
    public function __construct(
            $message, $code, $ref = '',
            $external_trace_as_string = '',
            \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->ref = (string) $ref;
        $this->external_trace = (string) $external_trace_as_string;
    }

    /**
     * @param string $class error class
     * @param string $error message
     * @param int    $code  code
     *
     * @return GeneralException
     */
    public static function buildFromBody(array $body)
    {
        $class = &$body['class'];
        $error = &$body['error'];
        $code = &$body['errno'];
        $ref = &$body['ref'];
        $trace = '';

        $class_name = sprintf(self::class_name_tpl, $class);

        // create exception fron devel mode
        $prev_ex = null;
        if (array_key_exists('devel_error', $body) &&
                is_array($body['devel_error']) && !empty($body['devel_error'])) {
            $prev_ex = self::buildFromBody($body['devel_error']);
        }
        if (!empty($body['trace'])) {
            $trace = $body['trace'];
        }
        // create exception object
        if (!class_exists($class_name)) {
            $class_name = __CLASS__;
        }
        /* @var $exception GeneralException */
        $exception = new $class_name($error, $code, $ref, $trace, $prev_ex);
        $exception->setApiExceptionClass($class);

        return $exception;
    }

    /**
     * get exception class.
     *
     * @return string
     */
    public function getClass()
    {
        return $this->exc_class;
    }

    /**
     * changes integer code to string defined in exception constraint.
     *
     * NOTE: slow don`t use in production enviroment
     *
     * @return string
     */
    public function getCodeAsString()
    {
        $refl = new \ReflectionClass($this);
        $constants = $refl->getConstants();
        $code = $this->getCode();
        $key = array_search($code, $constants, true);
        if (false === $key) {
            return self::error_not_found_text;
        }

        return $key;
    }

    /**
     * get the extrernal trace as string.
     *
     * @return string
     */
    public function getExternalTraceAsString()
    {
        return $this->external_trace;
    }

    /**
     * get reference to detailed error report.
     *
     * @return string
     */
    public function getReference()
    {
        return $this->ref;
    }

    /**
     * @return string
     */
    public function getApiExceptionClass()
    {
        return $this->api_exception_class;
    }

    /**
     * @param string $api_exception_class
     */
    public function setApiExceptionClass($api_exception_class)
    {
        $this->api_exception_class = (string) $api_exception_class;
    }
}
