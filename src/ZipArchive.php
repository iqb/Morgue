<?php

namespace iqb;

use iqb\stream\SubStream;
use iqb\zip\CentralDirectoryHeader;
use iqb\zip\EndOfCentralDirectory;
use iqb\zip\LocalFileHeader;

class ZipArchive
{
    /**
     * Ignore case on name lookup
     * @link http://php.net/manual/en/zip.constants.php
     */
    const FL_NOCASE = 1;

    /**
     * Ignore directory component
     * @link http://php.net/manual/en/zip.constants.php
     */
    const FL_NODIR = 2;

    /**
     * Read compressed data
     * @link http://php.net/manual/en/zip.constants.php
     */
    const FL_COMPRESSED = 4;

    /**
     * Use original data, ignoring changes.
     * @link http://php.net/manual/en/zip.constants.php
     */
    const FL_UNCHANGED = 8;

    /**
     * stored (uncompressed)
     */
    const CM_STORE = 0;

    /**
     * deflate compressed
     */
    const CM_DEFLATE = 8;

    /**
     * BZip2 compressed
     */
    const CM_BZIP2 = 12;

    /**
     * @var int
     */
    public $numFiles;

    /**
     * @var string
     */
    public $comment;

    /**
     * @var resource
     */
    private $handle;

    /**
     * @var CentralDirectoryHeader[]
     */
    public $originalCentralDirectory = [];

    /**
     * @var array(string => int)
     */
    public $originalCentralDirectoryNameToIndexMapping = [];

    /**
     * @var EndOfCentralDirectory
     */
    public $originalEndOfCentralDirectory;


    public function __construct()
    {
        if (!\in_array(SubStream::SCHEME, \stream_get_wrappers())) {
            \stream_wrapper_register(SubStream::SCHEME, SubStream::class);
        }
    }


    public function open(string $filename)
    {
        $this->handle = \fopen($filename, 'r+');

        // Find end of central directory
        $this->originalEndOfCentralDirectory = $this->findEndOfCentralDirectory();
        $this->comment = $this->originalEndOfCentralDirectory->zipFileComment;

        // Read central directory
        if (\fseek($this->handle, $this->originalEndOfCentralDirectory->offsetOfStartOfCentralDirectoryWithRespectToTheStartingDiskNumber) === -1) {
            throw new \RuntimeException("Unable to read Central Directory");
        }

        $centralDirectoryData = \fread($this->handle, $this->originalEndOfCentralDirectory->sizeOfTheCentralDirectory);
        $offset = 0;

        for ($i=0; $i<$this->originalEndOfCentralDirectory->totalNumberOfEntriesInTheCentralDirectory; $i++) {
            $centralDirectoryEntry = CentralDirectoryHeader::parse($centralDirectoryData, $offset);
            $offset += CentralDirectoryHeader::MIN_LENGTH;

            if ($centralDirectoryEntry->requireAdditionalData) {
                $offset += $centralDirectoryEntry->parseAdditionalData($centralDirectoryData, $offset);
            }

            $this->originalCentralDirectory[] = $centralDirectoryEntry;
            $this->originalCentralDirectoryNameToIndexMapping[$centralDirectoryEntry->fileName] = $i;
        }

        $this->numFiles = \count($this->originalCentralDirectory);
    }


    /**
     * @return EndOfCentralDirectory|null
     */
    private function findEndOfCentralDirectory()
    {
        $signature = \pack('N', EndOfCentralDirectory::SIGNATURE);

        for ($offset = EndOfCentralDirectory::MIN_LENGTH; $offset <= EndOfCentralDirectory::MAX_LENGTH; $offset++) {
            if (\fseek($this->handle, -$offset, \SEEK_END) === -1) {
                throw new \RuntimeException("Can not find EndOfDirectoryDirectory record");
            }

            $chunk = \fread($this->handle, EndOfCentralDirectory::MIN_LENGTH);
            if (\substr($chunk, 0, \strlen($signature)) !== $signature) {
                continue;
            }

            $endOfCentralDirectory = EndOfCentralDirectory::parse($chunk);
            if ($endOfCentralDirectory->requireAdditionalData) {
                $additionalData = \fread($this->handle, $endOfCentralDirectory->requireAdditionalData);
                $endOfCentralDirectory->parseAdditionalData($additionalData);
            }

            return $endOfCentralDirectory;
        }
    }


    /**
     * Returns the Zip archive comment
     *
     * @param int $flags ZipArchive::FL_UNCHANGED or 0
     * @return string|bool
     *
     * @link http://php.net/manual/en/ziparchive.getarchivecomment.php
     */
    final public function getArchiveComment(int $flags = 0)
    {
        if ($this->originalEndOfCentralDirectory->zipFileCommentLength > 0) {
            return $this->originalEndOfCentralDirectory->zipFileComment;
        } else {
            return false;
        }
    }


    /**
     * Returns the comment of an entry using the entry index.
     *
     * @param int $index Index of the entry
     * @param int $flags ZipArchive::FL_UNCHANGED or 0
     * @return string|false
     *
     * @link http://php.net/manual/en/ziparchive.getcommentindex.php
     */
    final public function getCommentIndex(int $index, int $flags = 0)
    {
        if (isset($this->originalCentralDirectory[$index])) {
            return $this->originalCentralDirectory[$index]->fileComment;
        } else {
            return false;
        }
    }


    /**
     * Returns the comment of an entry using the entry name
     *
     * @param string $name Name of the entry
     * @param int $flags ZipArchive::FL_UNCHANGED or 0
     * @return string|false
     *
     * @link http://php.net/manual/en/ziparchive.getcommentname.php
     */
    final public function getCommentName(string $name, int $flags = 0)
    {
        if (($index = $this->locateName($name, $flags & ZipArchive::FL_UNCHANGED)) !== false) {
            return $this->getCommentIndex($index, $flags);
        } else {
            return false;
        }
    }


    /**
     * @param int $index Index of the entry
     * @param int $length The length to be read from the entry. If 0, then the entire entry is read.
     * @param int $flags Any combination of ZipArchive::FL_UNCHANGED|ZipArchive::FL_COMPRESSED
     * @return string|false
     */
    final public function getFromIndex(int $index, int $length = 0, int $flags = 0)
    {
        if (($stream = $this->getStreamIndex($index, $flags)) === false) {
            return false;
        }

        $string = '';
        do {
            $chunkSize = ($length ? $length - \strlen($string) : 8192);
            if (($chunk = \fread($stream, $chunkSize)) !== false) {
                $string .= $chunk;
            } else {
                break;
            }
        } while (!\feof($stream) && ($length === 0 || \strlen($string) < $length));

        return $string;
    }


    /**
     * Get a file handle to the entry defined by its index (read only)
     *
     * @param int $index
     * @param int $flags
     * @return resource|false
     */
    final public function getStreamIndex(int $index, int $flags = 0)
    {
        if (!isset($this->originalCentralDirectory[$index])) {
            return false;
        } else {
            $entry = $this->originalCentralDirectory[$index];
        }

        if ($entry->compressedSize === 0) {
            return false;
        }

        \fseek($this->handle, $entry->relativeOffsetOfLocalHeader);
        $localHeader = LocalFileHeader::parse(\fread($this->handle, LocalFileHeader::MIN_LENGTH));
        if ($localHeader->requireAdditionalData) {
            $localHeader->parseAdditionalData(\fread($this->handle, $localHeader->requireAdditionalData));
        }

        $offset = \ftell($this->handle);
        $length = $entry->compressedSize;

        if (($handle = \fopen(SubStream::SCHEME . '://' . $offset . ':' . $length . '/' . (int)$this->handle, 'r')) === false) {
            return false;
        }

        if (($entry->compressionMethod === self::CM_STORE) || ($flags & self::FL_COMPRESSED)) {
            return $handle;
        }

        elseif ($entry->compressionMethod === self::CM_DEFLATE) {
            \stream_filter_append($handle, 'zlib.inflate', \STREAM_FILTER_READ);
            return $handle;
        }

        elseif ($entry->compressionMethod === self::CM_BZIP2) {
            \stream_filter_append($handle, 'bzip2.decompress', \STREAM_FILTER_READ);
            return $handle;
        }

        else {
            return false;
        }
    }


    /**
     * Returns the index of the entry in the archive
     *
     * @param string $name The name of the entry to look up
     * @param int $flags Any combination of ZipArchive::FL_NOCASE|ZipArchive::FL_NODIR
     * @return int|false
     *
     * @link http://php.net/manual/en/ziparchive.locatename.php
     */
    final public function locateName(string $name, int $flags = 0)
    {
        $ignoreCase = (($flags & self::FL_NOCASE) !== 0);
        $ignoreDir = (($flags & self::FL_NODIR) !== 0);

        if (!$ignoreCase && !$ignoreDir && isset($this->originalCentralDirectoryNameToIndexMapping[$name])) {
            return $this->originalCentralDirectoryNameToIndexMapping[$name];
        }

        $name = ($ignoreCase ? \strtolower($name) : $name);

        foreach ($this->originalCentralDirectory as $possibleIndex => $possibleEntry) {
            if ($ignoreDir && $possibleEntry->isDirectory()) {
                continue;
            }

            $entryName = $possibleEntry->fileName;
            $entryName = ($ignoreCase ? \strtolower($entryName) : $entryName);
            $entryName = ($ignoreDir ? \basename($entryName) : $entryName);

            if ($name === $entryName) {
                return $possibleIndex;
            }
        }

        return false;
    }


    /**
     * Get the details of an entry defined by its index
     *
     * @param int $index Index of the entry
     * @param int $flags ZipArchive::FL_UNCHANGED or 0
     * @return array|false
     *
     * @link http://php.net/manual/en/ziparchive.statindex.php
     */
    final public function statIndex(int $index, int $flags = 0)
    {
        if (!isset($this->originalCentralDirectory[$index])) {
            return false;
        }

        /* @var $entry CentralDirectoryHeader */
        $entry = $this->originalCentralDirectory[$index];

        return [
            'name' => $entry->fileName,
            'index' => $index,
            'crc' => $entry->crc32,
            'size' => $entry->uncompressedSize,
            'mtime' => $entry->lastModification->getTimestamp(),
            'comp_size' => $entry->compressedSize,
            'comp_method' => $entry->compressionMethod,
            'encryption_method' => 0,
        ];
    }


    /**
     * Get the details of an entry defined by its name
     *
     * @param string $name Name of the entry
     * @param int $flags Any combination of ZipArchive::FL_NOCASE|ZipArchive::FL_NODIR|ZipArchive::FL_UNCHANGED
     * @return array|false
     *
     * @link http://php.net/manual/en/ziparchive.statname.php
     */
    final public function statName(string $name, int $flags = 0)
    {
        if (($index = $this->locateName($name, $flags)) !== false) {
            return $this->statIndex($index, $flags);
        }

        return false;
    }
}
