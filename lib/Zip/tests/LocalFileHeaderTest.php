<?php

namespace morgue\zip;

use PHPUnit\Framework\TestCase;

final class LocalFileHeaderTest extends TestCase
{
    public function testImmutability()
    {
        $old = new LocalFileHeader(0,0,0,0,0,0,0,0);
        $number = 12345;

        $new = $old->setVersionNeededToExtract($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getVersionNeededToExtract(), $new->getVersionNeededToExtract());
        $this->assertSame($number, $new->getVersionNeededToExtract());
        $old = $new;
        $number += 123;

        $new = $old->setGeneralPurposeBitFlags($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getGeneralPurposeBitFlags(), $new->getGeneralPurposeBitFlags());
        $this->assertSame($number, $new->getGeneralPurposeBitFlags());
        $old = $new;
        $number += 123;

        $new = $old->setCompressionMethod($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getCompressionMethod(), $new->getCompressionMethod());
        $this->assertSame($number, $new->getCompressionMethod());
        $old = $new;
        $number += 123;

        $new = $old->setLastModificationFileDate($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getLastModificationFileDate(), $new->getLastModificationFileDate());
        $this->assertSame($number, $new->getLastModificationFileDate());
        $old = $new;
        $number += 123;

        $new = $old->setLastModificationFileTime($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getLastModificationFileTime(), $new->getLastModificationFileTime());
        $this->assertSame($number, $new->getLastModificationFileTime());
        $old = $new;
        $number += 123;

        $new = $old->setCrc32($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getCrc32(), $new->getCrc32());
        $this->assertSame($number, $new->getCrc32());
        $old = $new;
        $number += 123;

        $new = $old->setCompressedSize($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getCompressedSize(), $new->getCompressedSize());
        $this->assertSame($number, $new->getCompressedSize());
        $old = $new;
        $number += 123;

        $new = $old->setUncompressedSize($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getUncompressedSize(), $new->getUncompressedSize());
        $this->assertSame($number, $new->getUncompressedSize());
        $old = $new;

        $fileName = "new name";
        $new = $old->setFileName($fileName);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getFileName(), $new->getFileName());
        $this->assertSame($fileName, $new->getFileName());
        $this->assertSame(\strlen($fileName), $new->getFileNameLength());
        $old = $new;

        $extraField = "abcdefghij";
        $new = $old->setExtraField($extraField);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getExtraField(), $new->getextraField());
        $this->assertSame($extraField, $new->getExtraField());
        $this->assertSame(\strlen($extraField), $new->getExtraFieldLength());
    }
}
