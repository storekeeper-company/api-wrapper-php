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
    protected $filename;
    /**
     * @var string
     */
    protected $type;
    /**
     * @var string
     */
    protected $action_name;
    /**
     * @var string
     */
    protected $module_name;
    /**
     * @var string
     */
    protected $module_function;
    /**
     * @var
     */
    protected $return;

    /**
     * DumpFile constructor.
     */
    final public function __construct()
    {
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getActionName(): string
    {
        return $this->action_name;
    }

    public function getModuleName(): string
    {
        return $this->module_name;
    }

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
     * @param $type
     */
    public function setData(string $type, array $data): void
    {
        $this->type = $type;
        $this->setDataForType($type, $data);
        if (array_key_exists('return', $data)) {
            $this->return = $data['return'];
        }
        $this->data = $data;
    }

    protected function setDataForType(string $type, array $data): void
    {
        if (self::ACTION_TYPE === $type) {
            $this->action_name = $data['action'];
        } elseif (self::MODULE_TYPE === $type) {
            $this->module_name = $data['module_name'];
            $this->module_function = $data['function'];
        } else {
            throw new \RuntimeException("Unsupported type: '$type'");
        }
    }

    public static function getFilenamePartForType(string $type, Context $context): string
    {
        if (DumpFile::ACTION_TYPE === $type) {
            return "{$context['action']}.";
        } elseif (DumpFile::MODULE_TYPE === $type) {
            return "{$context['module_name']}::{$context['function']}.";
        }

        return '';
    }

    /**
     * @return array
     */
    public static function cleanContextFromSecretsForType(string $type, Context $context)
    {
        if (!empty($context['params']) && is_array($context['params'])) {
            self::cleanSecretValues($context['params']);
        }
    }

    protected static function cleanSecretValues(array &$array, array $keys = self::SECRET_KEYS)
    {
        $fn = self::setSecretValuesFn($keys);
        array_walk_recursive($array, $fn);
    }

    private static function setSecretValuesFn(array $keys = self::SECRET_KEYS): callable
    {
        return function (&$item, $key) use ($keys) {
            if (!is_numeric($key) && in_array($key, $keys)) {
                $item = self::SECRET_PLACEHOLDER;
            }
        };
    }

    /**
     * get key for return match.
     */
    public function getReturnMatchKey(): string
    {
        $type = $this->getType();
        $key = "$type.";
        if (DumpFile::ACTION_TYPE === $type) {
            $key .= $this->getActionName();
        } elseif (DumpFile::MODULE_TYPE === $type) {
            $key .= $this->getModuleName().'::'.$this->getModuleFunction();
        }

        return $key;
    }

    /**
     * get key for return match with parameter hash.
     *
     * @param null $params is null it will use the params from file
     */
    public function getReturnMatchKeyWithParams($params = null): string
    {
        if (is_null($params)) {
            $params = &$this->data['params'];
        }
        $key = $this->getReturnMatchKey();
        $key .= '.'.DumpFile::calculateDataHash($params);

        return $key;
    }

    /**
     * @param $data
     */
    public static function calculateDataHash($data): string
    {
        $data = self::normalizeDataForHash($data);
        $hash = json_encode($data);
        $hash = hash('sha256', $hash);

        return $hash;
    }

    private static function normalizeDataForHash($data)
    {
        if (is_array($data)) {
            ksort($data, SORT_NATURAL);
            foreach ($data as &$datum) {
                $datum = self::normalizeDataForHash($datum);
            }
        }

        return $data;
    }
}
