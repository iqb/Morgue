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
 * @coversDefaultClass \morgue\archive\Archive
 */
final class ArchiveTest extends TestCase
{
    public function testImmutability()
    {
        $old = new Archive();

        $value = 'new comment';
        $this->assertNotSame($value, $old->getComment());
        $new = $old->withComment($value);
        $this->assertNotSame($old, $new);
        $this->assertNotSame($old->getComment(), $new->getComment());
        $this->assertSame($value, $new->getComment());
        $old = $new;

        $this->assertEmpty($old->getEntries());

        $entry = new ArchiveEntry('entry1');
        $this->assertNotContains($entry, $old->getEntries());
        $new = $old->addEntry($entry);
        $this->assertNotSame($old, $new);
        $this->assertNotContains($entry, $old->getEntries());
        $this->assertContains($entry, $new->getEntries());
        $this->assertSame($entry, $new->getEntry(0));
        $this->assertSame($entry, $new->getEntry('entry1'));
        $old = $new;

        $new = $old
            ->addEntry(new ArchiveEntry('entry2'))
            ->addEntry($entry3 = new ArchiveEntry('entry3'))
            ->addEntry(new ArchiveEntry('entry4'))
            ->addEntry(new ArchiveEntry('entry5'))
        ;
        $this->assertNotSame($old, $new);
        $this->assertCount(5, $new->getEntries());
        $old = $new;

        $entry = $entry3->withComment('a comment');
        $this->assertContains($entry3, $old->getEntries());
        $this->assertNotContains($entry, $old->getEntries());

        $new = $old->replaceEntry($entry3, $entry);
        $this->assertNotSame($old, $new);
        $this->assertNotContains($entry, $old->getEntries());
        $this->assertContains($entry, $new->getEntries());
        $this->assertSame($entry3, $old->getEntry('entry3'));
        $this->assertSame($entry, $new->getEntry('entry3'));
        $old = $new;

        $new = $old->delEntry($entry);
        $this->assertNotSame($old, $new);
        $this->assertNotContains($entry, $new->getEntries());
        $this->assertCount(4, $new->getEntries());
        $this->assertNull($new->getEntry(2));
        $this->assertNull($new->getEntry('entry3'));
    }

    /**
     * @covers ::addEntry()
     */
    public function testAddEntry()
    {
        $archive = new Archive();
        $entry = new ArchiveEntry('entry');
        $archive = $archive->addEntry($entry);

        try {
            $archive->addEntry($entry);
            $this->fail('Except exception');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        }

        try {
            $archive->addEntry(new ArchiveEntry('entry2'), 0);
            $this->fail('Except exception');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        }

        try {
            $archive->addEntry(new ArchiveEntry('entry'));
            $this->fail('Except exception');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        }
    }

    /**
     * @covers  ::delEntry()
     */
    public function testDelEntry()
    {
        $archive = new Archive();
        $entry = new ArchiveEntry('entry');

        $archive = $archive->addEntry($entry);
        $this->assertCount(1, $archive->getEntries());

        $this->assertCount(0, $archive->delEntry(0)->getEntries());
        $this->assertCount(0, $archive->delEntry('entry')->getEntries());
        $this->assertCount(0, $archive->delEntry($entry)->getEntries());

        try {
            $archive->delEntry(1);
            $this->fail('Except exception');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        }

        try {
            $archive->delEntry('entry2');
            $this->fail('Except exception');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        }

        try {
            $archive->delEntry(new ArchiveEntry('entry2'));
            $this->fail('Except exception');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        }

        try {
            $archive->delEntry(new \SplObjectStorage());
            $this->fail('Except exception');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        }
    }

    /**
     * @covers ::getEntry()
     * @expectedException \InvalidArgumentException
     */
    public function testGetEntry()
    {
        $archive = new Archive();
        $archive->getEntry(new \SplObjectStorage());
    }

    /**
     * @covers ::replaceEntry()
     * @expectedException \InvalidArgumentException
     */
    public function testReplaceEntry()
    {
        $archive = new Archive();
        $archive->replaceEntry(new ArchiveEntry('entry1'), new ArchiveEntry('entry2'));
    }
}
