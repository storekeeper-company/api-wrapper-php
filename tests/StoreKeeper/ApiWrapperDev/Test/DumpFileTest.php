<?php

namespace StoreKeeper\ApiWrapperDev\Test;

use PHPUnit\Framework\TestCase;
use StoreKeeper\ApiWrapperDev\DumpFile;
use StoreKeeper\ApiWrapperDev\DumpFile\Context;
use StoreKeeper\ApiWrapperDev\DumpFile\Writer;
use StoreKeeper\ApiWrapperDev\Test\DumpFileTest\HookDumpFile;

class DumpFileTest extends TestCase
{
    public static function newDumpDir(): string
    {
        $tmp = sys_get_temp_dir().'/'.uniqid('phpunit_'.date('Ymd_Hi').'_');
        mkdir($tmp);

        return $tmp.DIRECTORY_SEPARATOR;
    }

    public function testCustomFile()
    {
        $expected_name = uniqid('return_');

        $secret = uniqid('Secret');
        $tmp = DumpFileTest::newDumpDir();
        $writer = new Writer($tmp);
        $writer->addExtraFileDumpType(HookDumpFile::HOOK_TYPE, HookDumpFile::class);
        $writer->withDump(HookDumpFile::HOOK_TYPE, function (Context $context) use ($expected_name, $secret) {
            $context['hook_name'] = $expected_name;
            $context['secret'] = $secret;
        });

        $files = $writer->getDumpedFiles();
        $this->assertNotEmpty($files, 'files were generated');

        $reader = new DumpFile\Reader();
        $reader->addExtraFileDumpType(HookDumpFile::HOOK_TYPE, HookDumpFile::class);

        $filename = $files[0];
        $this->assertStringContainsString($expected_name, $filename, 'Filename has hook name');

        $file = $reader->read($tmp.$filename);
        $this->assertEquals(HookDumpFile::class, get_class($file));
        /* @var $file \StoreKeeper\ApiWrapperDev\Test\DumpFileTest\HookDumpFile */
        $this->assertEquals($expected_name, $file->getHookName());
        $data = $file->getData();
        $this->assertEquals(HookDumpFile::SECRET_PLACEHOLDER, $data['secret'], 'secret');
    }

    public function testCalculateDataHash()
    {
        $expeced = 'e10eae147300c4d98e3ed5779734de11558eab4128dccd3f208d678aafed99a5';
        $data = [
            1 => '12',
            2 => 12,
            'a' => [
                'c' => 'a',
                'b' => 'a',
                'a' => 'a',
            ],
            0 => 'asd',
        ];
        $this->assertEquals($expeced, DumpFile::calculateDataHash($data));

        // try sorted array which produces the same hash
        $data = [
            'asd',
            '12',
            12,
            'a' => [
                'a' => 'a',
                'b' => 'a',
                'c' => 'a',
            ],
        ];
        $this->assertEquals($expeced, DumpFile::calculateDataHash($data));
    }
}
