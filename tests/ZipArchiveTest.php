<?php

namespace iqb;

use PHPUnit\Framework\TestCase;

class ZipArchiveTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // DOS date+time is stored as a local timezone variable, not UTC
        // libzip uses TZ environment variable to get the timezone whereas this package uses the PHP internal date timezone
        // Set both to UTC to avoid test problems system timezone settings
        \date_default_timezone_set('UTC');
        \putenv("TZ=UTC");
    }


    public function testStatIndex()
    {
        $fromExt = new \ZipArchive();
        $fromExt->open('test-no-extras.zip');

        $fromPkg = new ZipArchive();
        $fromPkg->open('test-no-extras.zip');

        $this->assertSame($fromExt->numFiles, $fromPkg->numFiles);
        $this->assertSame($fromExt->comment, $fromPkg->comment);

        for ($i=0; $i<$fromExt->numFiles; $i++) {
            $this->assertEquals($fromExt->statIndex($i), $fromPkg->statIndex($i));
        }
    }
}