<?php

namespace StoreKeeper\ApiWrapper\Test;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\ExpectationInterface;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use StoreKeeper\ApiWrapper\Auth\AnonymousAuth;
use StoreKeeper\ApiWrapperDev\DebugApiWrapper;
use StoreKeeper\ApiWrapperDev\DumpFile;
use StoreKeeper\ApiWrapperDev\Test\DumpFileTest;
use StoreKeeper\ApiWrapperDev\Test\TestLogger;
use StoreKeeper\ApiWrapperDev\Wrapper\MockAdapter;

/**
 * Class DebugApiWrapperTest.
 */
class DebugApiWrapperTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testLogger()
    {
        $logger = new TestLogger();

        $adapter = new MockAdapter();
        $wrapper = new DebugApiWrapper($adapter, new AnonymousAuth(''));
        $wrapper->setLogger($logger);

        $expect_return = uniqid('ret_');
        $adapter->withAction('testAction', function (ExpectationInterface $mockCall) use ($expect_return) {
            $mockCall->andReturn($expect_return);
        });

        $return = $wrapper->callAction('testAction', ['a', 'b']);
        $this->assertSame($expect_return, $return);
        $this->assertTrue($logger->hasInfoThatContains('DebugApiWrapper::'.DumpFile::ACTION_TYPE), 'logged DebugApiWrapper::callAction');

        $expect_return = uniqid('ret_');
        // add extra function added after
        $adapter->withModule('TestModule', function (MockInterface $module) use ($expect_return) {
            $module->shouldReceive('testFunction')->andReturn($expect_return);
        });

        $return = $wrapper->callFunction('TestModule', 'testFunction', ['a', 'b']);
        $this->assertSame($expect_return, $return, 'using callFunction');

        $this->assertTrue($logger->hasInfoThatContains('DebugApiWrapper::'.DumpFile::MODULE_TYPE), 'logged DebugApiWrapper::callFunction');
    }

    public function testDumping()
    {
        list($adapter, $wrapper, $tmp) = $this->getMockDumper();

        $expect_return = uniqid('ret_');
        // add extra function added after
        $adapter->withModule('TestModule', function (MockInterface $module) use ($expect_return) {
            $module->shouldReceive('testFunction')->andReturn($expect_return);
        });

        $return = $wrapper->callFunction('TestModule', 'testFunction', ['a', 'b', 'password' => 'ABC']);
        $this->assertSame($expect_return, $return, 'using callFunction');

        $files = $wrapper->getDumpWriter()->getDumpedFiles();
        $this->assertNotEmpty($files, 'files were generated');

        $reader = new DumpFile\Reader();
        $file = $reader->read($tmp.'/'.$files[0]);
        $fileData = $file->getData();

        $this->assertEquals($expect_return, $fileData['return']);
        $this->assertEquals(DumpFile::SECRET_PLACEHOLDER, $fileData['params']['password']);
    }

    public function testDumpingError()
    {
        list($adapter, $wrapper, $tmp) = $this->getMockDumper();

        $expect_exception = uniqid('Test');
        $adapter->withModule('TestModule', function (MockInterface $module) use ($expect_exception) {
            $module->shouldReceive('testFunction2')->andThrow(new \Exception($expect_exception));
        });

        try {
            $wrapper->callFunction('TestModule', 'testFunction2', ['a', 'b', 'password' => 'ABC']);
            $this->assertTrue(false, 'Exception was thrown');
        } catch (\Throwable $e) {
            $this->assertEquals($expect_exception, $e->getMessage());
        }

        $files = $wrapper->getDumpWriter()->getDumpedFiles();
        $this->assertNotEmpty($files, 'files were generated');

        $reader = new DumpFile\Reader();
        $file = $reader->read($tmp.'/'.$files[0]);
        $fileData = $file->getData();

        $this->assertEquals($expect_exception, $fileData['exception'], 'exception message');
        $this->assertEquals(get_class(new \Exception()), $fileData['exception_class'], 'exception class');
        $this->assertNotEmpty($fileData['exception_trace'], 'exception trace');
        $this->assertEquals(DumpFile::SECRET_PLACEHOLDER, $fileData['params']['password']);
    }

    public function getMockDumper(): array
    {
        $adapter = new MockAdapter();
        $wrapper = new DebugApiWrapper($adapter, new AnonymousAuth(''));

        $tmp = DumpFileTest::newDumpDir();
        $wrapper->enableDumping($tmp);

        return [$adapter, $wrapper, $tmp];
    }
}
