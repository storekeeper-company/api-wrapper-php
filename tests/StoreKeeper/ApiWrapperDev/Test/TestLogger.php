<?php

namespace StoreKeeper\ApiWrapperDev\Test;

use Psr\Log\AbstractLogger;

/**
 * php 7.3 compatible version of colinodell/psr-testlogger:1.1.
 *
 * @see https://github.com/colinodell/psr-testlogger/blob/v1.1.0/src/TestLogger.php
 * can be removed after not having dependency on the 7.3 anymore
 */
class TestLogger extends AbstractLogger
{
    /** @var array<int, array<string, mixed>> */
    public $records = [];

    /** @var array<int|string, array<int, array<string, mixed>>> */
    public $recordsByLevel = [];

    /**
     * {@inheritDoc}
     *
     * @param array<array-key, mixed> $context
     */
    public function log($level, $message, array $context = []): void
    {
        $record = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];

        $this->recordsByLevel[$record['level']][] = $record;
        $this->records[] = $record;
    }

    /**
     * @param LogLevel::* $level
     */
    public function hasRecords(string $level): bool
    {
        return isset($this->recordsByLevel[$level]);
    }

    /**
     * @param string|array<string, mixed> $record
     * @param LogLevel::*                 $level
     */
    public function hasRecord($record, string $level): bool
    {
        if (\is_string($record)) {
            $record = ['message' => $record];
        }

        return $this->hasRecordThatPasses(static function (array $rec) use ($record) {
            if ($rec['message'] !== $record['message']) {
                return false;
            }

            return !isset($record['context']) || $rec['context'] === $record['context'];
        }, $level);
    }

    /**
     * @param LogLevel::* $level
     */
    public function hasRecordThatContains(string $message, string $level): bool
    {
        return $this->hasRecordThatPasses(static function (array $rec) use ($message) {
            return false !== \strpos($rec['message'], $message);
        }, $level);
    }

    /**
     * @param LogLevel::* $level
     */
    public function hasRecordThatMatches(string $regex, string $level): bool
    {
        return $this->hasRecordThatPasses(static function ($rec) use ($regex) {
            return \preg_match($regex, $rec['message']) > 0;
        }, $level);
    }

    /**
     * @param callable(array<string, mixed>, int): bool $predicate
     * @param LogLevel::*                               $level
     */
    public function hasRecordThatPasses(callable $predicate, string $level): bool
    {
        if (!isset($this->recordsByLevel[$level])) {
            return false;
        }

        foreach ($this->recordsByLevel[$level] as $i => $rec) {
            if (\call_user_func($predicate, $rec, $i)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, mixed> $args
     */
    public function __call(string $method, array $args): bool
    {
        if (\preg_match('/(.*)(Debug|Info|Notice|Warning|Error|Critical|Alert|Emergency)(.*)/', $method, $matches) > 0) {
            $genericMethod = $matches[1].('Records' !== $matches[3] ? 'Record' : '').$matches[3];
            $callable = [$this, $genericMethod];
            $level = \strtolower($matches[2]);
            if (\is_callable($callable)) {
                $args[] = $level;

                return \call_user_func_array($callable, $args);
            }
        }

        throw new \BadMethodCallException('Call to undefined method '.static::class.'::'.$method.'()');
    }

    public function reset(): void
    {
        $this->records = [];
        $this->recordsByLevel = [];
    }
}
