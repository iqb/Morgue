<?php

/*
 * (c) 2018 Dennis Birkholz <dennis@birkholz.org>
 *
 * $Id$
 * Author:    $Format:%an <%ae>, %ai$
 * Committer: $Format:%cn <%ce>, %ci$
 */

namespace morgue\archive;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \morgue\archive\ArchiveEntry
 */
final class ArchiveEntryTest extends TestCase
{
    public function testImmutability()
    {
        $old = new ArchiveEntry('name');

        $value = 'new name';
        $this->assertNotSame($value, $old->getName());
        $new = $old->withName($value);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getName(), $new->getName());
        $this->assertSame($value, $new->getName());
        $old = $new;

        $value = 12345;
        $this->assertNotSame($value, $old->getUncompressedSize());
        $new = $old->withUncompressedSize($value);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getUncompressedSize(), $new->getUncompressedSize());
        $this->assertSame($value, $new->getUncompressedSize());
        $old = $new;

        $value = 23456;
        $this->assertNotSame($value, $old->getSourceSize());
        $new = $old->withSourceSize($value);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getSourceSize(), $new->getSourceSize());
        $this->assertSame($value, $new->getSourceSize());
        $old = $new;

        $value = 34567;
        $this->assertNotSame($value, $old->getTargetSize());
        $new = $old->withTargetSize($value);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getTargetSize(), $new->getTargetSize());
        $this->assertSame($value, $new->getTargetSize());
        $old = $new;

        $value = new \DateTimeImmutable();
        $this->assertNotSame($value, $old->getCreationTime());
        $new = $old->withCreationTime($value);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getCreationTime(), $new->getCreationTime());
        $this->assertSame($value, $new->getCreationTime());
        $old = $new;

        $value = new \DateTimeImmutable();
        $this->assertNotSame($value, $old->getModificationTime());
        $new = $old->withModificationTime($value);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getModificationTime(), $new->getModificationTime());
        $this->assertSame($value, $new->getModificationTime());
        $old = $new;

        $value = 45678;
        $this->assertNotSame($value, $old->getChecksumCrc32());
        $new = $old->withChecksumCrc32($value);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getChecksumCrc32(), $new->getChecksumCrc32());
        $this->assertSame($value, $new->getChecksumCrc32());
        $old = $new;

        $value = 'another comment';
        $this->assertNotSame($value, $old->getComment());
        $new = $old->withComment($value);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getComment(), $new->getComment());
        $this->assertSame($value, $new->getComment());
        $old = $new;

        $value = COMPRESSION_METHOD_LZMA;
        $this->assertNotSame($value, $old->getSourceCompressionMethod());
        $new = $old->withSourceCompressionMethod($value);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getSourceCompressionMethod(), $new->getSourceCompressionMethod());
        $this->assertSame($value, $new->getSourceCompressionMethod());
        $old = $new;

        $value = COMPRESSION_METHOD_BZIP2;
        $this->assertNotSame($value, $old->getTargetCompressionMethod());
        $new = $old->withTargetCompressionMethod($value);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getTargetCompressionMethod(), $new->getTargetCompressionMethod());
        $this->assertSame($value, $new->getTargetCompressionMethod());
        $old = $new;

        $value = 56789;
        $this->assertNotSame($value, $old->getDosAttributes());
        $new = $old->withDosAttributes($value);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getDosAttributes(), $new->getDosAttributes());
        $this->assertSame($value, $new->getDosAttributes());
        $old = $new;

        $value = 67890;
        $this->assertNotSame($value, $old->getUnixAttributes());
        $new = $old->withUnixAttributes($value);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getUnixAttributes(), $new->getUnixAttributes());
        $this->assertSame($value, $new->getUnixAttributes());
    }

    /**
     * @covers ::getSourcePath()
     * @covers ::withSourcePath()
     * @covers ::importStat()
     */
    public function testSourcePath()
    {
        $filename = __DIR__ . '/ipsum.txt';
        $stat = \stat($filename);

        $entryEmpty = new ArchiveEntry(\basename($filename));
        $entry = $entryEmpty->withSourcePath($filename, COMPRESSION_METHOD_STORE);

        $this->assertNotSame($entryEmpty, $entry);
        $this->assertSame($filename, $entry->getSourcePath());
        $this->assertNull($entry->getSourceStream());
        $this->assertNull($entry->getSourceString());
        $this->assertSame(COMPRESSION_METHOD_STORE, $entry->getSourceCompressionMethod());
        $this->assertSame($stat['size'], $entry->getSourceSize());
        $this->assertSame($stat['size'], $entry->getUncompressedSize());
        $this->assertSame($stat['mode'], $entry->getUnixAttributes());
        $this->assertSame($stat['ctime'], $entry->getCreationTime()->getTimestamp());
        $this->assertSame($stat['mtime'], $entry->getModificationTime()->getTimestamp());
    }

    /**
     * @covers ::getSourcePath()
     * @covers ::withSourcePath()
     * @covers ::importStat()
     */
    public function testSourcePathDirectory()
    {
        $filename = __DIR__;
        $stat = \stat($filename);

        $entryEmpty = new ArchiveEntry(\basename($filename));
        $entry = $entryEmpty->withSourcePath($filename);

        $this->assertNotSame($entryEmpty, $entry);
        $this->assertSame($filename, $entry->getSourcePath());
        $this->assertNull($entry->getSourceStream());
        $this->assertNull($entry->getSourceString());
        $this->assertSame(COMPRESSION_METHOD_STORE, $entry->getSourceCompressionMethod());
        $this->assertSame(COMPRESSION_METHOD_STORE, $entry->getTargetCompressionMethod());
        $this->assertSame(0, $entry->getSourceSize());
        $this->assertSame(0, $entry->getTargetSize());
        $this->assertSame(0, $entry->getUncompressedSize());
        $this->assertSame($stat['mode'], $entry->getUnixAttributes());
        $this->assertSame($stat['ctime'], $entry->getCreationTime()->getTimestamp());
        $this->assertSame($stat['mtime'], $entry->getModificationTime()->getTimestamp());
    }

    /**
     * @covers ::getSourceStream()
     * @covers ::withSourceStream()
     * @covers ::importStat()
     */
    public function testSourceStream()
    {
        $filename = __DIR__ . '/ipsum.txt';
        $stream = \fopen($filename, 'r');
        $stat = \fstat($stream);

        $entryEmpty = new ArchiveEntry(\basename($filename));
        $entry = $entryEmpty->withSourceStream($stream, COMPRESSION_METHOD_STORE);

        $this->assertNotSame($entryEmpty, $entry);
        $this->assertNull($entry->getSourcePath());
        $this->assertSame($stream, $entry->getSourceStream());
        $this->assertNull($entry->getSourceString());
        $this->assertSame(COMPRESSION_METHOD_STORE, $entry->getSourceCompressionMethod());
        $this->assertSame($stat['size'], $entry->getSourceSize());
        $this->assertSame($stat['size'], $entry->getUncompressedSize());
        $this->assertSame($stat['mode'], $entry->getUnixAttributes());
        $this->assertSame($stat['ctime'], $entry->getCreationTime()->getTimestamp());
        $this->assertSame($stat['mtime'], $entry->getModificationTime()->getTimestamp());
    }

    /**
     * @covers ::getSourceString()
     * @covers ::withSourceString()
     */
    public function testSourceString()
    {
        $filename = __DIR__ . '/ipsum.txt';
        $string = \file_get_contents($filename);

        $entryEmpty = new ArchiveEntry(\basename($filename));
        $timestamp1 = \time();
        $entry = $entryEmpty->withSourceString($string, COMPRESSION_METHOD_STORE);
        $timestamp2 = \time();

        $this->assertNotSame($entryEmpty, $entry);
        $this->assertNull($entry->getSourcePath());
        $this->assertNull($entry->getSourceStream());
        $this->assertSame($string, $entry->getSourceString());
        $this->assertSame(COMPRESSION_METHOD_STORE, $entry->getSourceCompressionMethod());
        $this->assertSame(\strlen($string), $entry->getSourceSize());
        $this->assertSame(\strlen($string), $entry->getUncompressedSize());
        $this->assertNull($entry->getUnixAttributes());
        $this->assertTrue($timestamp1 <= $entry->getCreationTime()->getTimestamp() && $entry->getCreationTime()->getTimestamp() <= $timestamp2);
        $this->assertTrue($timestamp1 <= $entry->getModificationTime()->getTimestamp() && $entry->getCreationTime()->getTimestamp() <= $timestamp2);
    }

    /**
     * @covers ::withSourcePath()
     * @expectedException \InvalidArgumentException
     */
    public function testSourcePathExceptionInvalidFile()
    {
        $entry = new ArchiveEntry('entry');
        $entry->withSourcePath(__DIR__ . '/non-existing-file');
    }

    /**
     * @covers ::withSourcePath()
     * @expectedException \InvalidArgumentException
     */
    public function testSourcePathExceptionPathSet()
    {
        $entryEmpty = new ArchiveEntry('entry');
        $entry = $entryEmpty->withSourcePath(__DIR__ . '/ipsum.txt');
        $entry->withSourcePath(__FILE__);
    }

    /**
     * @covers ::withSourcePath()
     * @expectedException \InvalidArgumentException
     */
    public function testSourcePathExceptionStreamSet()
    {
        $entryEmpty = new ArchiveEntry('entry');
        $entry = $entryEmpty->withSourceStream(\fopen('php://memory', 'r'));
        $entry->withSourcePath(__FILE__);
    }

    /**
     * @covers ::withSourcePath()
     * @expectedException \InvalidArgumentException
     */
    public function testSourcePathExceptionStringSet()
    {
        $entryEmpty = new ArchiveEntry('entry');
        $entry = $entryEmpty->withSourceString('content');
        $entry->withSourcePath(__FILE__);
    }

    /**
     * @covers ::withSourceStream()
     * @expectedException \InvalidArgumentException
     */
    public function testSourceStreamExceptionPathSet()
    {
        $entryEmpty = new ArchiveEntry('entry');
        $entry = $entryEmpty->withSourcePath(__DIR__ . '/ipsum.txt');
        $entry->withSourceStream(\fopen('php://memory', 'r'));
    }

    /**
     * @covers ::withSourceStream()
     * @expectedException \InvalidArgumentException
     */
    public function testSourceStreamExceptionStreamSet()
    {
        $entryEmpty = new ArchiveEntry('entry');
        $entry = $entryEmpty->withSourceStream(\fopen('php://memory', 'r'));
        $entry->withSourceStream(\fopen('php://memory', 'r'));
    }

    /**
     * @covers ::withSourceStream()
     * @expectedException \InvalidArgumentException
     */
    public function testSourceStreamExceptionStringSet()
    {
        $entryEmpty = new ArchiveEntry('entry');
        $entry = $entryEmpty->withSourceString('content');
        $entry->withSourceStream(\fopen('php://memory', 'r'));
    }

    /**
     * @covers ::withSourceString()
     * @expectedException \InvalidArgumentException
     */
    public function testSourceStringExceptionPathSet()
    {
        $entryEmpty = new ArchiveEntry('entry');
        $entry = $entryEmpty->withSourcePath(__DIR__ . '/ipsum.txt');
        $entry->withSourceString('content');
    }

    /**
     * @covers ::withSourceString()
     * @expectedException \InvalidArgumentException
     */
    public function testSourceStringExceptionStreamSet()
    {
        $entryEmpty = new ArchiveEntry('entry');
        $entry = $entryEmpty->withSourceStream(\fopen('php://memory', 'r'));
        $entry->withSourceString('content');
    }

    /**
     * @covers ::withSourceString()
     * @expectedException \InvalidArgumentException
     */
    public function testSourceStringExceptionStringSet()
    {
        $entryEmpty = new ArchiveEntry('entry');
        $entry = $entryEmpty->withSourceString('content');
        $entry->withSourceString('content');
    }

    /**
     * @covers ::getSourceAsStream()
     */
    public function testGetSourceAsStreamFromPath()
    {
        $filename = __DIR__ . '/ipsum.txt';
        $stream = (new ArchiveEntry(\basename($filename)))->withSourcePath($filename)->getSourceAsStream();

        $this->assertTrue(\is_resource($stream));
        $this->assertSame(\file_get_contents($filename), \stream_get_contents($stream));
    }

    /**
     * @covers ::getSourceAsStream()
     */
    public function testGetSourceAsStreamFromStream()
    {
        $filename = __DIR__ . '/ipsum.txt';
        $stream = (new ArchiveEntry(\basename($filename)))->withSourceStream(\fopen($filename, 'r'))->getSourceAsStream();

        $this->assertTrue(\is_resource($stream));
        $this->assertSame(\file_get_contents($filename), \stream_get_contents($stream));
    }

    /**
     * @covers ::getSourceAsStream()
     */
    public function testGetSourceAsStreamFromString()
    {
        $filename = __DIR__ . '/ipsum.txt';
        $stream = (new ArchiveEntry(\basename($filename)))->withSourceString(\file_get_contents($filename))->getSourceAsStream();

        $this->assertTrue(\is_resource($stream));
        $this->assertSame(\file_get_contents($filename), \stream_get_contents($stream));
    }
}
