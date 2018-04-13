<?php

namespace morgue\zip;

use PHPUnit\Framework\TestCase;

final class EndOfCentralDirectoryTest extends TestCase
{
    public function testImmutability()
    {
        $old = new EndOfCentralDirectory(0,0,0,0,0,0);
        $number = 12345;

        $new = $old->setNumberOfThisDisk($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getNumberOfThisDisk(), $new->getNumberOfThisDisk());
        $this->assertSame($number, $new->getNumberOfThisDisk());
        $old = $new;
        $number += 123;

        $new = $old->setNumberOfTheDiskWithTheStartOfTheCentralDirectory($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getNumberOfTheDiskWithTheStartOfTheCentralDirectory(), $new->getNumberOfTheDiskWithTheStartOfTheCentralDirectory());
        $this->assertSame($number, $new->getNumberOfTheDiskWithTheStartOfTheCentralDirectory());
        $old = $new;
        $number += 123;

        $new = $old->setTotalNumberOfEntriesInTheCentralDirectoryOnThisDisk($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getTotalNumberOfEntriesInTheCentralDirectoryOnThisDisk(), $new->getTotalNumberOfEntriesInTheCentralDirectoryOnThisDisk());
        $this->assertSame($number, $new->getTotalNumberOfEntriesInTheCentralDirectoryOnThisDisk());
        $old = $new;
        $number += 123;

        $new = $old->setTotalNumberOfEntriesInTheCentralDirectory($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getTotalNumberOfEntriesInTheCentralDirectory(), $new->getTotalNumberOfEntriesInTheCentralDirectory());
        $this->assertSame($number, $new->getTotalNumberOfEntriesInTheCentralDirectory());
        $old = $new;
        $number += 123;

        $new = $old->setSizeOfTheCentralDirectory($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getSizeOfTheCentralDirectory(), $new->getSizeOfTheCentralDirectory());
        $this->assertSame($number, $new->getSizeOfTheCentralDirectory());
        $old = $new;
        $number += 123;

        $new = $old->setOffsetOfStartOfCentralDirectoryWithRespectToTheStartingDiskNumber($number);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getOffsetOfStartOfCentralDirectoryWithRespectToTheStartingDiskNumber(), $new->getOffsetOfStartOfCentralDirectoryWithRespectToTheStartingDiskNumber());
        $this->assertSame($number, $new->getOffsetOfStartOfCentralDirectoryWithRespectToTheStartingDiskNumber());
        $old = $new;

        $fileComment = "a comment";
        $new = $old->setZipFileComment($fileComment);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getZipFileComment(), $new->getZipFileComment());
        $this->assertSame($fileComment, $new->getZipFileComment());
        $this->assertSame(\strlen($fileComment), $new->getZipFileCommentLength());
    }
}
