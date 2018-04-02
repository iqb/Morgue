<?php

namespace iqb;

use iqb\zip\CentralDirectoryHeader;
use iqb\zip\EndOfCentralDirectory;

class ZipArchive
{
    /// @var int
    public $numFiles;

    /// @var string
    public $comment;

    /// @var resource
    private $handle;

    /// @var CentralDirectoryHeader[]
    public $originalCentralDirectory = [];

    /// @var array(string => int)
    public $originalCentralDirectoryNameToIndexMapping = [];

    /// @var EndOfCentralDirectory
    public $originalEndOfCentralDirectory;


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
     * @param int $index
     * @return array|false
     */
    final public function statIndex(int $index)
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
}
