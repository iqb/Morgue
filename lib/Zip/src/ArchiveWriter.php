<?php

/*
 * (c) 2018 Dennis Birkholz <dennis@birkholz.org>
 *
 * $Id$
 * Author:    $Format:%an <%ae>, %ai$
 * Committer: $Format:%cn <%ce>, %ci$
 */

namespace morgue\zip;

use morgue\archive\Archive;
use morgue\archive\ArchiveWriterInterface;

/**
 * @author Dennis Birkholz <dennis@birkholz.org>
 */
final class ArchiveWriter implements ArchiveWriterInterface
{
    /**
     * @param Archive $archive
     * @param resource $targetStream
     * @return Archive The archive updated with information only available after writing it
     */
    public function write(Archive $archive, $targetStream) : Archive
    {
        $centralDirectory = [];

        foreach ($archive->getEntries() as $archiveEntry) {
            // Write local file header
            $entryStartPosition = \ftell($targetStream);
            $localFileHeader = LocalFileHeader::createFromArchiveEntry($archiveEntry);
            \fwrite($targetStream, $localFileHeader->marshal());

            // Write file content
            $fileStartPosition = \ftell($targetStream);
            \stream_copy_to_stream($archiveEntry->getSourceStream(), $targetStream);

            // Position where next entry will be written
            $nextEntryPosition = \ftell($targetStream);

            // Re-write local file header with additional information known after compression only
            $localFileHeader = $localFileHeader->setCompressedSize($nextEntryPosition - $fileStartPosition);
            \fseek($targetStream, $entryStartPosition);
            \fwrite($targetStream, $localFileHeader->marshal());

            // Update archive entry with additional information known after compression only
            $archive = $archive->replaceEntry($archiveEntry, $archiveEntry->withTargetSize($localFileHeader->getCompressedSize()));

            // Move into position for next entry
            \fseek($targetStream, $nextEntryPosition);

            // Create central directory entry
            $centralDirectory[] = CentralDirectoryHeader::createFromArchiveEntry($archiveEntry)
                ->setRelativeOffsetOfLocalHeader($entryStartPosition)
            ;
        }

        $centralDirectorySize = 0;
        $centralDirectoryStart = \ftell($targetStream);
        foreach ($centralDirectory as $centralDirectoryHeader) {
            $centralDirectorySize += \fwrite($targetStream, $centralDirectoryHeader->encode());
        }

        $endOfCentralDirectory = new EndOfCentralDirectory(
            0,
            0,
            \count($centralDirectory),
            \count($centralDirectory),
            $centralDirectorySize,
            $centralDirectoryStart,
            $archive->getComment()
        );
        \fwrite($targetStream, $endOfCentralDirectory->marshal());

        return $archive;
    }
}
