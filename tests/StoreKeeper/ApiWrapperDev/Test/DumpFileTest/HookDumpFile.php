<?php

namespace StoreKeeper\ApiWrapperDev\Test\DumpFileTest;

use StoreKeeper\ApiWrapperDev\DumpFile;
use StoreKeeper\ApiWrapperDev\DumpFile\Context;

class HookDumpFile extends DumpFile
{
    public const HOOK_TYPE = 'hook';

    protected $hook_name;

    protected function setDataForType(string $type, array $data): void
    {
        if (self::HOOK_TYPE === $type) {
            $this->hook_name = $data['hook_name'];
        } else {
            parent::setDataForType($type, $data);
        }
    }

    /**
     * @return mixed
     */
    public function getHookName()
    {
        return $this->hook_name;
    }

    public static function getFilenamePartForType(string $type, Context $context): string
    {
        if (self::HOOK_TYPE === $type) {
            return "{$context['hook_name']}.";
        }

        return parent::getFilenamePartForType($type, $context);
    }

    /**
     * @return array
     */
    public static function cleanContextFromSecretsForType(string $type, Context $context)
    {
        parent::cleanContextFromSecretsForType($type, $context);
        $context['secret'] = self::SECRET_PLACEHOLDER;
    }
}
