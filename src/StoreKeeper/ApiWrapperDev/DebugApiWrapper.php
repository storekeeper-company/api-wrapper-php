<?php

namespace StoreKeeper\ApiWrapperDev;

use StoreKeeper\ApiWrapper\ApiWrapper;
use StoreKeeper\ApiWrapper\Auth;
use StoreKeeper\ApiWrapperDev\DumpFile\Context;
use StoreKeeper\ApiWrapperDev\DumpFile\Writer;

class DebugApiWrapper extends ApiWrapper
{
    /**
     * @var bool if the parameters should logged
     */
    protected $log_params = false;
    /**
     * @var bool if the in out data should be dumped to directory
     */
    protected $dumping = false;
    /**
     * @var Writer
     */
    protected $dump_writer;

    public function isDumping(): bool
    {
        return $this->dumping;
    }

    public function setDumping(bool $dumping): void
    {
        $this->dumping = $dumping;
    }

    public function enableDumping(string $dump_directory): void
    {
        $this->setDumping(true);
        $this->dump_writer = new Writer($dump_directory);
    }

    public function getDumpWriter(): Writer
    {
        return $this->dump_writer;
    }

    /**
     * @param $type
     *
     * @return mixed
     *
     * @throws \Throwable
     */
    protected function withDebug($type, callable $call)
    {
        $context = new Context();
        $context->setCallId();
        $context->startTimer();
        try {
            $return = $call($context);
            if ($this->isDumping()) {
                $this->dump_writer->writeSuccess($type, $return, $context);
            }
            $this->logger->info("DebugApiWrapper::$type", $this->prepareLoggerContext($context));
        } catch (\Throwable $e) {
            if ($this->isDumping()) {
                $this->dump_writer->writeError($type, $e, $context);
            } else {
                // done by writeError if dumping
                $context->setThrowable($e);
            }
            $this->logger->error("DebugApiWrapper::$type", $this->prepareLoggerContext($context));
            throw $e;
        }

        return $return;
    }

    public function callAction($action, array $params = [])
    {
        return $this->withDebug(DumpFile::ACTION_TYPE, function (\ArrayObject $context) use ($action, $params) {
            $context['action'] = $action;
            $context['params'] = $params;

            return parent::callAction($action, $params);
        });
    }

    public function callFunction($module_name, $name, array $params = [], Auth $auth = null)
    {
        return $this->withDebug(DumpFile::MODULE_TYPE, function (\ArrayObject $context) use ($module_name, $name, $params, $auth) {
            $context['module_name'] = $module_name;
            $context['function'] = $name;
            $context['params'] = $params;

            return parent::callFunction($module_name, $name, $params, $auth);
        });
    }

    protected function prepareLoggerContext(Context $context): array
    {
        $context->stopTimer();
        $result_context = $context->toArray();
        // clean before save
        unset($result_context['exception_trace']);
        if (!$this->log_params) {
            unset($result_context['params']);
        }

        return $result_context;
    }
}
