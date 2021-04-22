<?php

namespace StoreKeeper\ApiWrapperDev\DumpFile;

use StoreKeeper\ApiWrapperDev\DumpFile;

class Reader
{
    use TypeDependentTrait;

    /**
     * @param string $data
     * @param $filepath
     *
     * @return mixed
     */
    public static function decode(string $json, string $filepath = ''): array
    {
        $data = json_decode($json, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \RuntimeException("Failed decoding json from $filepath: ".json_last_error_msg());
        }
        if (!is_array($data)) {
            throw new \RuntimeException("Decoded data from $filepath is structure. Current type is ".gettype($data));
        }

        return $data;
    }

    /**
     * @param $filepath
     */
    public function read($filepath): DumpFile
    {
        $file = new \SplFileInfo($filepath);
        if (!$file->isFile() || !$file->isReadable()) {
            throw new \RuntimeException("File $filepath is not a readable file");
        }
        if (0 === $file->getSize()) {
            throw new \RuntimeException("File $filepath is empty");
        }
        $data = file_get_contents($filepath);
        $json = self::decode($data, $filepath);
        $version = $json[DumpFile::DUMP_VERSION_KEY];

        if ('1.0' !== $version) {
            throw new \RuntimeException("Unsupported version: '$version'");
        }

        $type = $json[DumpFile::DUMP_TYPE_KEY];

        $class = $this->getClassForFileDumpType($type);
        /* @var $dump \StoreKeeper\ApiWrapperDev\DumpFile */
        $dump = new $class();
        $dump->setData($type, $json);

        return $dump;
    }
}
