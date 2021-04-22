<?php


namespace StoreKeeper\ApiWrapperDev;


use StoreKeeper\ApiWrapperDev\DumpFile\Context;

class DumpFile
{

    const SECRET_KEYS = ['password', 'secret', 'apikey', 'hash', 'pass'];
    const SECRET_PLACEHOLDER = '(...SECRET...)';
    const DUMP_VERSION = '1.0';

    const ACTION_TYPE = 'action';
    const MODULE_TYPE = 'moduleFunction';

    const DUMP_TYPE_KEY = '_type';
    const DUMP_VERSION_KEY = '_version';
    const DUMP_TIMESTAMP_KEY = '_timestamp';
    /**
     * @var array
     */
    protected $data = [];
    /**
     * @var string
     */
    protected $filename ;
    /**
     * @var string
     */
    protected $type ;
    /**
     * @var string
     */
    protected $action_name ;
    /**
     * @var string
     */
    protected $module_name ;
    /**
     * @var string
     */
    protected $module_function ;
    /**
     * @var
     */
    protected $return ;

    /**
     * DumpFile constructor.
     */
    final function __construct()
    {
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     */
    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }
    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getActionName(): string
    {
        return $this->action_name;
    }

    /**
     * @return string
     */
    public function getModuleName(): string
    {
        return $this->module_name;
    }

    /**
     * @return string
     */
    public function getModuleFunction(): string
    {
        return $this->module_function;
    }

    /**
     * @return mixed
     */
    public function getReturn()
    {
        return $this->return;
    }

    /**
     * @param array $data
     * @param $type
     */
    function setData(string $type, array $data): void
    {
        $this->type = $type;
        $this->setDataForType($type, $data);
        if( array_key_exists('return', $data)){
            $this->return = $data['return'];
        }
        $this->data = $data;
    }

    /**
     * @param string $type
     * @param array $data
     */
    protected function setDataForType(string $type, array $data): void
    {
        if ($type === self::ACTION_TYPE) {
            $this->action_name = $data['action'];
        } else if ($type === self::MODULE_TYPE) {
            $this->module_name     = $data['module_name'];
            $this->module_function = $data['function'];
        } else {
            throw new \RuntimeException("Unsupported type: '$type'");
        }
    }
    /**
     * @param string $type
     * @param Context $context
     *
     * @return string
     */
    static function getFilenamePartForType(string $type, Context $context): string
    {
        if ($type === DumpFile::ACTION_TYPE) {
            return "{$context['action']}.";
        } else if ($type === DumpFile::MODULE_TYPE) {
            return "{$context['module_name']}::{$context['function']}.";
        }
        return '';
    }

    /**
     * @param string $type
     * @param Context $context
     *
     * @return array
     */
    static function cleanContextFromSecretsForType(string $type, Context $context)
    {
        if( !empty($context['params']) && is_array($context['params']) ){
            self::cleanSecretValues($context['params']);
        }
    }

    static protected function cleanSecretValues(array & $array, array $keys = self::SECRET_KEYS)
    {
        $fn = self::setSecretValuesFn($keys);
        array_walk_recursive($array, $fn);
    }

    static private function setSecretValuesFn(array $keys = self::SECRET_KEYS): callable
    {
        return function(&$item, $key) use( $keys )
        {
            if( !is_numeric($key) && in_array($key, $keys ) ){
                $item = self::SECRET_PLACEHOLDER;
            }
        };
    }

    /**
     * get key for return match
     * @return string
     */
    function getReturnMatchKey(): string{
        $type = $this->getType();
        $key = "$type.";
        if ($type === DumpFile::ACTION_TYPE) {
            $key .=  $this->getActionName();
        } else if ($type === DumpFile::MODULE_TYPE) {
            $key .= $this->getModuleName()."::".$this->getModuleFunction();
        }
        return $key;
    }

    /**
     * get key for return match with parameter hash
     * @param null $params is null it will use the params from file
     *
     * @return string
     */
    function getReturnMatchKeyWithParams($params = null): string{
        if( is_null($params) ){
            $params = & $this->data['params'];
        }
        $key = $this->getReturnMatchKey();
        $key .= '.'.DumpFile::calculateDataHash($params);
        return $key;
    }
    /**
     * @param $data
     *
     * @return string
     */
    static function calculateDataHash($data): string{
        $data = self::normalizeDataForHash($data);
        $hash = json_encode($data);
        $hash = hash("sha256", $hash);
        return $hash;
    }

    private static function normalizeDataForHash($data) {
        if( is_array($data) ){
            ksort($data, SORT_NATURAL);
            foreach ($data as &$datum){
                $datum = self::normalizeDataForHash($datum);
            }
        }
        return $data;
    }
}