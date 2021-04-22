<?php

namespace StoreKeeper\ApiWrapperDev\DumpFile;

use StoreKeeper\ApiWrapperDev\DumpFile;

class Writer
{
    use TypeDependentTrait;
    /**
     * @var string where to dump the data
     */
    protected $dumping_directory = false;
    /**
     * @var string[] location of filed which ware written
     */
    protected $dumped_files = [];

    /**
     * Writer constructor.
     */
    public function __construct(string $dumping_directory)
    {
        $this->setDumpingDirectory($dumping_directory);
    }

    /**
     * @return false|string
     */
    public static function encode(array $data)
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    public function getDumpingDirectory(): string
    {
        return $this->dumping_directory;
    }

    /**
     * @return string[]
     */
    public function getDumpedFiles(): array
    {
        return $this->dumped_files;
    }

    /**
     * @return \SplFileInfo[]
     */
    public function getDumpedFileInfos(): array
    {
        $file_infos = [];
        foreach ($this->dumped_files as $file) {
            $file_infos[$file] = new \SplFileInfo($this->dumping_directory.'/'.$file);
        }

        return $file_infos;
    }

    public function setDumpingDirectory(string $dumping_directory): void
    {
        if (!is_dir($dumping_directory)) {
            if (!mkdir($dumping_directory, 0777, true)) {
                throw new \RuntimeException("$dumping_directory cannot be created");
            }
        }
        if (!is_writable($dumping_directory) || !is_dir($dumping_directory)) {
            throw new \RuntimeException("$dumping_directory is not a writable directory");
        }

        $this->dumping_directory = $dumping_directory;
    }

    /**
     * @param $type
     *
     * @return mixed
     *
     * @throws \Throwable
     */
    public function withDump(string $type, callable $call)
    {
        $context = new Context();
        $context->setCallId();
        $context->startTimer();
        try {
            $return = $call($context);
            $this->writeSuccess($type, $return, $context);
        } catch (\Throwable $e) {
            $this->writeError($type, $e, $context);
            throw $e;
        }

        return $return;
    }

    /**
     * @param $type
     * @param $return
     */
    public function writeSuccess($type, $return, Context $context): string
    {
        $this->cleanContextFromSecretsForType($type, $context);

        $context['return'] = $return;
        $context[DumpFile::DUMP_TYPE_KEY] = $type;

        $filename = $this->getDumpFilename($type, true, $context);
        $this->dumpData($filename, $context);

        return $filename;
    }

    /**
     * @param $type
     */
    public function writeError($type, \Throwable $e, Context $context): string
    {
        $this->cleanContextFromSecretsForType($type, $context);

        $context->setThrowable($e);
        $context[DumpFile::DUMP_TYPE_KEY] = $type;

        $filename = $this->getDumpFilename($type, false, $context);
        $this->dumpData($filename, $context);

        return $filename;
    }

    /**
     * @param $name
     */
    protected function getDumpFilename($name, bool $success, Context $context): string
    {
        $filename = date('Ymd_His').".$name.";
        $filename .= $this->getFilenamePartForType($name, $context);
        $filename .= $success ? 'success.' : 'error.';
        $filename .= $context['call_id'];
        $filename .= '.json';

        return $filename;
    }

    /**
     * @param string $type
     */
    protected function dumpData(string $filename, Context $context)
    {
        $context->stopTimer();
        $data = $context->toArray();
        $data[DumpFile::DUMP_VERSION_KEY] = DumpFile::DUMP_VERSION;
        $data[DumpFile::DUMP_TIMESTAMP_KEY] = date('c');
        $json = self::encode($data);

        if (!file_put_contents(
            $this->dumping_directory.DIRECTORY_SEPARATOR.$filename,
            $json
        )) {
            throw new \RuntimeException("Failed to save to $filename");
        } else {
            $this->dumped_files[] = $filename;
        }
    }

    /**
     * @param $type
     * @param string $filename
     */
    protected function getFilenamePartForType($type, Context $context): string
    {
        $class = $this->getClassForFileDumpType($type);

        return call_user_func("$class::getFilenamePartForType", $type, $context);
    }

    /**
     * Gets data cleaned from any passwords and secrets.
     *
     * @param $type
     *
     * @return array
     */
    protected function cleanContextFromSecretsForType($type, Context $context)
    {
        $class = $this->getClassForFileDumpType($type);
        call_user_func("$class::cleanContextFromSecretsForType", $type, $context);
    }
}
