<?php

namespace morgue\zip;

use PHPUnit\Framework\TestCase;

final class CentralDirectoryHeaderTest extends TestCase
{
    public function testImmutability()
    {
        $number = 12345;
        $old = new CentralDirectoryHeader(0,0,0,0,0,0,0,0,0,0,0,0,0);

        $new = $old->setVersionMadeBy($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getVersionMadeBy(), $new->getVersionMadeBy());
        $this->assertSame($number, $new->getVersionMadeBy());
        $old = $new;
        $number += 123;

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

        $new = $old->setLastModificationFileTime($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getLastModificationFileTime(), $new->getLastModificationFileTime());
        $this->assertSame($number, $new->getLastModificationFileTime());
        $old = $new;
        $number += 123;

        $new = $old->setLastModificationFileDate($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getLastModificationFileDate(), $new->getLastModificationFileDate());
        $this->assertSame($number, $new->getLastModificationFileDate());
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
        $number += 123;

        $new = $old->setDiskNumberStart($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getDiskNumberStart(), $new->getDiskNumberStart());
        $this->assertSame($number, $new->getDiskNumberStart());
        $old = $new;
        $number += 123;

        $new = $old->setInternalFileAttributes($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getInternalFileAttributes(), $new->getInternalFileAttributes());
        $this->assertSame($number, $new->getInternalFileAttributes());
        $old = $new;
        $number += 123;

        $new = $old->setExternalFileAttributes($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getExternalFileAttributes(), $new->getExternalFileAttributes());
        $this->assertSame($number, $new->getExternalFileAttributes());
        $old = $new;
        $number += 123;

        $new = $old->setRelativeOffsetOfLocalHeader($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getRelativeOffsetOfLocalHeader(), $new->getRelativeOffsetOfLocalHeader());
        $this->assertSame($number, $new->getRelativeOffsetOfLocalHeader());
        $old = $new;
        $number += 123;

        $new = $old->setEncodingHost($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getEncodingHost(), $new->getEncodingHost());
        $this->assertSame($number, $new->getEncodingHost());
        $old = $new;
        $number += 123;

        $new = $old->setEncodingVersion($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getEncodingVersion(), $new->getEncodingVersion());
        $this->assertSame($number, $new->getEncodingVersion());
        $old = $new;
        $number += 123;

        $new = $old->setRequiredHost($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getRequiredHost(), $new->getRequiredHost());
        $this->assertSame($number, $new->getRequiredHost());
        $old = $new;
        $number += 123;

        $new = $old->setRequiredVersion($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getRequiredVersion(), $new->getRequiredVersion());
        $this->assertSame($number, $new->getRequiredVersion());
        $old = $new;
        $number += 123;

        $new = $old->setDosExternalAttributes($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getDosExternalAttributes(), $new->getDosExternalAttributes());
        $this->assertSame($number, $new->getDosExternalAttributes());
        $old = $new;
        $number += 123;

        $new = $old->setUnixExternalAttributes($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getUnixExternalAttributes(), $new->getUnixExternalAttributes());
        $this->assertSame($number, $new->getUnixExternalAttributes());

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
        $old = $new;

        $fileComment = "a comment";
        $new = $old->setFileComment($fileComment);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getFileComment(), $new->getFileComment());
        $this->assertSame($fileComment, $new->getFileComment());
        $this->assertSame(\strlen($fileComment), $new->getFileCommentLength());
    }
}
