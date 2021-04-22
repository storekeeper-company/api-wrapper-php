<?php

namespace StoreKeeper\ApiWrapperDev;

use PHPUnit\Framework\TestCase;
use StoreKeeper\ApiWrapper\ApiWrapper;
use StoreKeeper\ApiWrapper\Auth\AnonymousAuth;
use StoreKeeper\ApiWrapper\Wrapper\FullJsonAdapter;

class TestEnvLoader
{
    /**
     * @var string
     */
    protected static $projectRoot;

    public static function getProjectRoot(): string
    {
        if (empty(self::$projectRoot)) {
            throw new \RuntimeException(get_called_class().'::$projectRoot is empty');
        }

        return self::$projectRoot;
    }

    public static function setProjectRoot(string $projectRoot): string
    {
        self::$projectRoot = realpath($projectRoot);
        if (empty(self::$projectRoot) || !is_dir(self::$projectRoot)) {
            throw new \RuntimeException("$projectRoot is not a directory");
        }
        self::$projectRoot .= DIRECTORY_SEPARATOR;

        return self::$projectRoot;
    }

    public static function loadDotEnv(string $projectRoot = null)
    {
        if (!empty($projectRoot)) {
            self::setProjectRoot($projectRoot);
        }
        $root = self::getProjectRoot();
        $filepath = $root.'/.env.test.local';

        if (is_readable($filepath)) {
            $dotenv = new \Symfony\Component\Dotenv\Dotenv();
            $dotenv->load($filepath);
        }
    }

    /**
     * skips test if env variables are not defined.
     */
    public static function skipIfNotSetUp(array $vars, TestCase $test)
    {
        $empty = [];
        foreach ($vars as $var) {
            if (empty($_ENV[$var])) {
                $empty[] = "\$_ENV['$var']";
            }
        }
        if (!empty($empty)) {
            $root = self::getProjectRoot();
            $test->markTestSkipped(
                'Empty vars: '.implode(', ', $empty)
                .", define them in $root.env.test.local file or pass them as environmental variables");
        }
    }

    public static function getAnonymousApiWrapper(TestCase $test): ApiWrapper
    {
        self::skipIfNotSetUp(['STOREKEEPER_API_URL', 'STOREKEEPER_API_ACCOUNT'], $test);

        return new ApiWrapper(
            new FullJsonAdapter($_ENV['STOREKEEPER_API_URL']),
            new AnonymousAuth($_ENV['STOREKEEPER_API_ACCOUNT'])
        );
    }
}
