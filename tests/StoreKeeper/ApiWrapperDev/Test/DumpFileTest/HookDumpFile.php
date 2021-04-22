<?php


namespace StoreKeeper\ApiWrapperDev\Test\DumpFileTest;

use StoreKeeper\ApiWrapperDev\DumpFile;
use StoreKeeper\ApiWrapperDev\DumpFile\Context;

class HookDumpFile extends DumpFile
{
    const HOOK_TYPE = 'hook';

    protected $hook_name;
    /**
     * @param string $type
     * @param array $data
     */
    protected function setDataForType(string $type, array $data): void
    {
        if ($type === self::HOOK_TYPE) {
            $this->hook_name = $data['hook_name'];
        } else {
            parent::setDataForType($type,$data);
        }
    }

    /**
     * @return mixed
     */
    public function getHookName()
    {
        return $this->hook_name;
    }


    /**
     * @param string $type
     * @param Context $context
     *
     * @return string
     */
    static function getFilenamePartForType(string $type, Context $context): string
    {
        if ($type === self::HOOK_TYPE) {
            return "{$context['hook_name']}.";
        }
        return parent::getFilenamePartForType($type,$context);
    }

    /**
     * @param string $type
     * @param Context $context
     *
     * @return array
     */
    static function cleanContextFromSecretsForType(string $type, Context $context)
    {
        parent::cleanContextFromSecretsForType($type,$context);
        $context['secret'] = self::SECRET_PLACEHOLDER;
    }
}