<?php

namespace StoreKeeper\ApiWrapperDev\Test\Wrapper;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\ExpectationInterface;
use Mockery\MockInterface;
use org\bovigo\vfs\content\LargeFileContent;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use StoreKeeper\ApiWrapper\ApiWrapper;
use StoreKeeper\ApiWrapper\Auth\AnonymousAuth;
use StoreKeeper\ApiWrapperDev\DebugApiWrapper;
use StoreKeeper\ApiWrapperDev\TestEnvLoader;
use StoreKeeper\ApiWrapperDev\Wrapper\MockAdapter;

class MockAdapterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testRegisteredAction()
    {
        $adapter = new MockAdapter();
        $wrapper = new ApiWrapper($adapter, new AnonymousAuth(''));

        $expect_return = uniqid('ret');
        $adapter->withAction('testAction', function (ExpectationInterface $mockCall) use ($expect_return) {
            $mockCall->andReturn($expect_return);
        });

        $return = $wrapper->callAction('testAction', ['a', 'b']);
        $this->assertSame($expect_return, $return);
    }

    public function testRegisteredModuleApiWrapper()
    {
        $adapter = new MockAdapter();
        $wrapper = new ApiWrapper($adapter, new AnonymousAuth(''));

        $expect_return = uniqid('ret');
        $adapter->withModule('TestModule', function (MockInterface $module) use ($expect_return) {
            $module->shouldReceive('testFunction')->andReturn($expect_return);
        });
        // add extra function added after
        $adapter->withModule('TestModule', function (MockInterface $module) {
            $module->shouldReceive('testFunction2')->andReturn('extra');
        });

        $return = $wrapper->callFunction('TestModule', 'testFunction', ['a', 'b']);
        $this->assertSame($expect_return, $return, 'using callFunction');

        $return = $wrapper->getModule('TestModule')->testFunction('a', 'b');
        $this->assertSame($expect_return, $return, 'using call on object');

        $return = $wrapper->TestModule->testFunction('a', 'b');
        $this->assertSame($expect_return, $return, 'using call on object from name');

        $return = $wrapper->callFunction('TestModule', 'testFunction2', ['a', 'b']);
        $this->assertSame('extra', $return, 'testFunction2 using callFunction');
    }

    /**
     * @throws \Exception
     *
     * @depends testRegisteredModuleApiWrapper
     * @depends testRegisteredAction
     */
    public function testMockFromDumpEmptyArrayReturn()
    {
        $adapter = new MockAdapter();
        $wrapper = new DebugApiWrapper($adapter, new AnonymousAuth(''));
        $tmp = sys_get_temp_dir().'/'.uniqid('phpunit_'.date('Ymd_Hi').'_');
        mkdir($tmp);
        $wrapper->enableDumping($tmp);

        $expect_return_module = [];

        $adapter->withModule('TestModule', function (MockInterface $module) use ($expect_return_module) {
            $module->shouldReceive('testFunction')->andReturn($expect_return_module);
        });
        $wrapper->callFunction('TestModule', 'testFunction', ['a', '1']);

        // check if it was dumped
        $files = $wrapper->getDumpWriter()->getDumpedFiles();
        $this->assertCount(1, $files, 'dumped files count');

        // make new adapter
        $adapter = new MockAdapter();
        $wrapper = new ApiWrapper($adapter, new AnonymousAuth(''));
        $adapter->registerDumpFiles([$files[0]], $tmp, true);

        $return = $wrapper->callFunction('TestModule', 'testFunction', ['a', '1']);
        $this->assertSame($expect_return_module, $return, 'with params module function');
    }

    /**
     * @throws \Exception
     *
     * @depends testRegisteredModuleApiWrapper
     * @depends testRegisteredAction
     */
    public function testMockFromDump()
    {
        $adapter = new MockAdapter();
        $wrapper = new DebugApiWrapper($adapter, new AnonymousAuth(''));
        $tmp = sys_get_temp_dir().'/'.uniqid('phpunit_'.date('Ymd_Hi').'_');
        mkdir($tmp);
        $wrapper->enableDumping($tmp);

        $expect_return_module = uniqid('ret_');
        $expect_return_action = uniqid('ret');

        // dumping phase
        $adapter->withAction('testAction', function (ExpectationInterface $mockCall) use ($expect_return_action) {
            $mockCall->andReturn($expect_return_action);
        });
        $wrapper->callAction('testAction', ['a', 'b']);
        $adapter->withModule('TestModule', function (MockInterface $module) use ($expect_return_module) {
            $module->shouldReceive('testFunction')
                ->andReturnValues([
                    $expect_return_module.'_1', // 2 calls
                    $expect_return_module.'_2',
                ]);
        });
        $wrapper->callFunction('TestModule', 'testFunction', ['a', '1']);
        // -> returns $expect_return_module.'_1'
        $wrapper->callFunction('TestModule', 'testFunction', ['a', '2']);
        // -> returns $expect_return_module.'_2'

        // check if it was dumped
        $files = $wrapper->getDumpWriter()->getDumpedFiles();
        $this->assertCount(3, $files, 'dumped files count');

        // make new adapter
        $adapter = new MockAdapter();
        $wrapper = new ApiWrapper($adapter, new AnonymousAuth(''));
        $adapter->registerDumpFiles([$files[0]], $tmp);
        $adapter->registerDumpFiles([$files[1]], $tmp); // first register any call
        $adapter->registerDumpFiles([$files[2]], $tmp, true); // first register with param check

        // test if loaded correctly
        $return = $wrapper->callAction('testAction', ['c', 'd']);
        $this->assertSame($expect_return_action, $return, 'call action');

        $return = $wrapper->callFunction('TestModule', 'testFunction', ['asdasd', 'as1']);
        $this->assertSame($expect_return_module.'_1', $return, 'any module function');
        $return = $wrapper->callFunction('TestModule', 'testFunction', ['a', '2']);
        $this->assertSame($expect_return_module.'_2', $return, 'with params module function');

        $used_returns = $adapter->getUsedReturns();
        $this->assertCount(3, $used_returns);
    }

    /**
     * @throws \Exception
     */
    public function testRegisteredMixedModuleApiWrapper()
    {
        $realWrapper = TestEnvLoader::getAnonymousApiWrapper($this);
        $adapter = new MockAdapter();
        $wrapper = new ApiWrapper($adapter, new AnonymousAuth(''));

        $expect_return = uniqid('ret');
        $adapter->withOriginalModule($realWrapper, 'ManagementModule', function (MockInterface $module) use ($expect_return) {
            $module->shouldReceive('testFunction')->andReturn($expect_return);
        });

        // this one does the real call to backend
        $return = $wrapper->callFunction('ManagementModule', 'getTime');
        $this->assertIsInt(strtotime($return), 'using getTime');

        $return = $wrapper->callFunction('ManagementModule', 'testFunction');
        $this->assertSame($expect_return, $return, 'using callFunction');
    }

    public function testResourceRootSetting()
    {
        $rootDir = uniqid('unit_test_');
        $root = vfsStream::setup($rootDir);
        $url = $root->url();

        $adapter = new MockAdapter();
        $adapter->setResourceUrl($url);

        $smallFilePath = '/file.png';
        file_put_contents($url.$smallFilePath, 'ABC');
        $data = file_get_contents($adapter->getResourceUrl().$smallFilePath);
        $this->assertNotEmpty($data, 'File returned');

        $largeFilePath = '/a/b/c/large.txt';
        vfsStream::newFile(ltrim($largeFilePath, '/'))
                              ->withContent(LargeFileContent::withGigabytes(100))
                              ->at($root);
        $this->assertEquals(107374182400, filesize(
            $adapter->getResourceUrl().$largeFilePath
        ));
    }
}
