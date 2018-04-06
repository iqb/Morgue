<?php

namespace iqb;

/**
 * Test reading of the supported compression methods
 */
final class CompressionMethodsTest extends ZipArchiveTestBase
{
    public function testBZip2()
    {
        if (self::$libzipFeatures[ZipArchive::CM_BZIP2]) {
            $this->runMethodTest('getFromIndex', self::ZIP_BZIP2, [0]);
        } else {
            $this->markTestSkipped('\ZipArchive does not support BZip2');
        }
    }

    public function testDeflate()
    {
        $this->runMethodTest('getFromIndex', self::ZIP_DEFLATE, [0]);
    }

    public function testStore()
    {
        $this->runMethodTest('getFromIndex', self::ZIP_STORE, [0]);
    }

    public function testBZip2Internal()
    {
        $zip = new ZipArchive();

        if ($zip->bzip2Support()) {
            $zip->open(self::ZIP_BZIP2);
            $this->assertSame(\file_get_contents(self::REFERENCE_FILE), $zip->getFromIndex(0));
        } else {
            $this->markTestSkipped('ext-bz2 is not installed');
        }
    }

    public function testDeflateInternal()
    {
        $zip = new ZipArchive();

        if ($zip->deflateSupport()) {
            $zip->open(self::ZIP_DEFLATE);
            $this->assertSame(\file_get_contents(self::REFERENCE_FILE), $zip->getFromIndex(0));
        } else {
            $this->markTestSkipped('ext-zlib is not installed');
        }
    }

    public function testStoreInternal()
    {
        $zip = new ZipArchive();
        $zip->open(self::ZIP_STORE);
        $this->assertSame(\file_get_contents(self::REFERENCE_FILE), $zip->getFromIndex(0));
    }

    public function testUnsupported()
    {
        if (!self::$libzipFeatures[ZipArchive::CM_PPMD]) {
            $fileName = self::ZIP_PPMD;
        }

        elseif (!self::$libzipFeatures[ZipArchive::CM_DEFLATE64]) {
            $fileName = self::ZIP_DEFLATE64;
        }

        else {
            $this->markTestSkipped("Could not find a compression method that is not supported by \ZipArchive");
            return;
        }

        $this->runMethodTest('getFromIndex', $fileName, [0]);
    }
}
