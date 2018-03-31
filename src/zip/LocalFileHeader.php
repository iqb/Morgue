<?php

namespace iqb\zip;

class LocalFileHeader
{
    const SIGNATURE = 0x504b0304;

    const MIN_LENGTH = 30;

    /// @var int
    public $versionNeededToExtract;

    /// @var int
    public $generalPurposeBitFlags;

    /// @var int
    public $compressionMethod;

    /// @var int
    public $lastModificationFileDate;

    /// @var int
    public $lastModificationFileTime;

    /// @var int
    public $crc32;

    /// @var int
    public $compressedSize;

    /// @var int
    public $uncompressedSize;

    /// @var int
    public $fileNameLength;

    /// @var int
    public $extraFieldLength;

    /// @var string
    public $fileName;

    /// @var string
    public $extraField;

    /// @var int
    public $requireAdditionalData;


    public function __construct(
        int $versionNeededToExtract,
        int $generalPurposeBitFlags,
        int $compressionMethod,
        int $lastModificationFileTime,
        int $lastModificationFileDate,
        int $crc32,
        int $compressedSize,
        int $uncompressedSize,
        string $fileName = null,
        string $extraField = null
    ) {
        $this->versionNeededToExtract = $versionNeededToExtract;
        $this->generalPurposeBitFlags = $generalPurposeBitFlags;
        $this->compressionMethod = $compressionMethod;
        $this->lastModificationFileTime = $lastModificationFileTime;
        $this->lastModificationFileDate = $lastModificationFileDate;
        $this->compressedSize = $compressedSize;
        $this->uncompressedSize = $uncompressedSize;

        if ($fileName !== null) {
            $this->fileName = $fileName;
            $this->fileNameLength = \strlen($this->fileName);
        }
        if ($extraField !== null) {
            $this->extraField = $extraField;
            $this->extraFieldLength = \strlen($this->extraField);
        }
    }


    /**
     * Parse the local file header from a binary string.
     * Check $requireAdditionalData to check if parseAdditionalData() must be called to parse additional fields.
     *
     * @param string $input
     * @param int $offset Start at this position inside the string
     * @return static
     */
    public static function parse(string $input, int $offset = 0)
    {
        if (\strlen($input) < ($offset+self::MIN_LENGTH)) {
            throw new \InvalidArgumentException("Not enough data to parse local file header!");
        }

        $parsed = \unpack(
            'Nsignature'
            . '/vversionNeededToExtract'
            . '/vgeneralPurposeBitFlags'
            . '/vcompressionMethod'
            . '/vlastModificationFileTime'
            . '/vlastModificationFileDate'
            . '/Vcrc32'
            . '/VcompressedSize'
            . '/VuncompressedSize'
            . '/vfileNameLength'
            . '/vextraFieldLength',
            $input,
            $offset
        );
        if ($parsed['signature'] !== self::SIGNATURE) {
            throw new \InvalidArgumentException("Invalid signature for local file header!");
        }

        $localFileHeader = new static(
            $parsed['versionNeededToExtract'],
            $parsed['generalPurposeBitFlags'],
            $parsed['compressionMethod'],
            $parsed['lastModificationFileTime'],
            $parsed['lastModificationFileDate'],
            $parsed['crc32'],
            $parsed['compressedSize'],
            $parsed['uncompressedSize']
        );
        $localFileHeader->fileNameLength = $parsed['fileNameLength'];
        $localFileHeader->extraFieldLength = $parsed['extraFieldLength'];
        $localFileHeader->requireAdditionalData = $localFileHeader->fileNameLength + $localFileHeader->extraFieldLength;

        return $localFileHeader;
    }


    /**
     * After a new object has been created by parse(), this method must be called to initialize the file name and extra field entries which have dynamic field length.
     * The required number of bytes is written to the $requireAdditionalData attribute by parse().
     *
     * @param string $input
     * @param int $offset
     * @return int
     */
    public function parseAdditionalData(string $input, int $offset = 0) : int
    {
        if ($this->fileName !== null || $this->extraField !== null) {
            throw new \BadMethodCallException("Additional data already parsed!");
        }

        if (!$this->requireAdditionalData) {
            throw new \BadMethodCallException("No additional data required!");
        }

        if (\strlen($input) < ($offset + $this->fileNameLength + $this->extraFieldLength)) {
            throw new \InvalidArgumentException("Not enough input to parse additional data!");
        }

        $this->fileName = \substr($input, $offset, $this->fileNameLength);
        $this->extraField = bin2hex(\substr($input, $offset+$this->fileNameLength, $this->extraFieldLength));
        $this->requireAdditionalData = null;

        return $this->fileNameLength + $this->extraFieldLength;
    }
}
