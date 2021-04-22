<?php

namespace StoreKeeper\ApiWrapperDev\DumpFile;

use StoreKeeper\ApiWrapperDev\DumpFile;

trait TypeDependentTrait
{
    protected $default_file_dump_class = DumpFile::class;
    protected $extra_file_dump_types = [];

    public function getDefaultFileDumpClass(): string
    {
        return $this->default_file_dump_class;
    }

    public function setDefaultFileDumpClass(string $default_file_dump_class): void
    {
        self::checkFileDumpClass($default_file_dump_class);
        $this->default_file_dump_class = $default_file_dump_class;
    }

    public function getExtraFileDumpTypes(): array
    {
        return $this->extra_file_dump_types;
    }

    /**
     * @return $this
     */
    public function addExtraFileDumpType(string $type, string $class): self
    {
        self::checkFileDumpClass($class);
        $this->extra_file_dump_types[$type] = $class;

        return $this;
    }

    protected static function checkFileDumpClass(string $class): void
    {
        if (!is_a($class, DumpFile::class, true)) {
            throw new \RuntimeException("$class is not ".DumpFile::class);
        }
    }

    /**
     * @param $type
     */
    public function getClassForFileDumpType($type): string
    {
        if (array_key_exists($type, $this->extra_file_dump_types)) {
            return $this->extra_file_dump_types[$type];
        }

        return $this->default_file_dump_class;
    }
}
