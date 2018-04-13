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
use morgue\archive\ArchiveReaderInterface;
use const iqb\stream\SUBSTREAM_SCHEME;

/**
 * @author Dennis Birkholz <dennis@birkholz.org>
 */
final class ArchiveReader implements ArchiveReaderInterface
{
    public function read($stream, int $offset = 0): Archive
    {
        $meta = \stream_get_meta_data($stream);
        if (!$meta['seekable']) {
            throw new \InvalidArgumentException('Zip archive can only be read from a seekable stream.');
        }

        $endOfCentralDirectory = $this->findEndOfCentralDirectory($stream);

        // Read central directory binary representation
        if (\fseek($stream, $offset + $endOfCentralDirectory->getOffsetOfStartOfCentralDirectoryWithRespectToTheStartingDiskNumber()) === -1) {
            throw new \RuntimeException("Unable to read Central Directory");
        }
        $centralDirectoryData = \fread($stream, $endOfCentralDirectory->getSizeOfTheCentralDirectory());

        $archive = new Archive();
        $archive = $archive->withComment($endOfCentralDirectory->getZipFileComment());

        // Read entries from central directory
        for ($position = 0, $i=0; $i<$endOfCentralDirectory->getTotalNumberOfEntriesInTheCentralDirectory(); $i++) {
            // Parse central directory entry incl. variable length fields
            $centralDirectoryEntry = CentralDirectoryHeader::parse($centralDirectoryData, $position);
            $position += CentralDirectoryHeader::MIN_LENGTH;
            if ($centralDirectoryEntry->getVariableLength() > 0) {
                $position += $centralDirectoryEntry->parseAdditionalData($centralDirectoryData, $position);
            }

            // Seek to local file header, parse it incl. variable length fields
            // File handle position is now at the start of the file content
            \fseek($stream, $offset + $centralDirectoryEntry->getRelativeOffsetOfLocalHeader());
            $localHeader = LocalFileHeader::parse(\fread($stream, LocalFileHeader::MIN_LENGTH));
            if ($localHeader->getVariableLength() > 0) {
                $localHeader->parseAdditionalData(\fread($stream, $localHeader->getVariableLength()));
            }

            // Create archive entry and attach content stream for non-directories
            $archiveEntry = $centralDirectoryEntry->toArchiveEntry();
            if (!$centralDirectoryEntry->isDirectory()) {
                $archiveEntry = $archiveEntry->withSourceStream(
                    \fopen(SUBSTREAM_SCHEME . '://' . \ftell($stream) . ':' . $centralDirectoryEntry->getCompressedSize() . '/' . (int)$stream, 'r')
                );
            }

            $archive = $archive->addEntry($archiveEntry);
        }

        return $archive;
    }

    /**
     * @param resource $stream
     * @return EndOfCentralDirectory
     */
    private function findEndOfCentralDirectory($stream)
    {
        $signature = \pack('N', EndOfCentralDirectory::SIGNATURE);

        for ($offset = EndOfCentralDirectory::MIN_LENGTH; $offset <= EndOfCentralDirectory::MAX_LENGTH; $offset++) {
            if (\fseek($stream, -$offset, \SEEK_END) === -1) {
                throw new \RuntimeException("Can not find EndOfDirectoryDirectory record");
            }

            $chunk = \fread($stream, EndOfCentralDirectory::MIN_LENGTH);
            if (\substr($chunk, 0, \strlen($signature)) !== $signature) {
                continue;
            }

            $endOfCentralDirectory = EndOfCentralDirectory::parse($chunk);
            if ($endOfCentralDirectory->getVariableLength() > 0) {
                $additionalData = \fread($stream, $endOfCentralDirectory->getVariableLength());
                $endOfCentralDirectory->parseAdditionalData($additionalData);
            }

            return $endOfCentralDirectory;
        }

        throw new \RuntimeException("Unable to read Central Directory");
    }
}
