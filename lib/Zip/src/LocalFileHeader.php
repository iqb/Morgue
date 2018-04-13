<?php

namespace morgue\zip;

use morgue\archive\ArchiveEntry;

final class LocalFileHeader
{
    const SIGNATURE = 0x504b0304;

    /// Minimum length of this entry if neither file name not extra field are set
    const MIN_LENGTH = 30;

    /// Maximum length of this entry file name and extra field have the maximum allowed length
    const MAX_LENGTH = self::MIN_LENGTH + self::FILE_NAME_MAX_LENGTH + self::EXTRA_FIELD_MAX_LENGTH;

    /// File name can not be longer than this (the length field has only 2 bytes)
    const FILE_NAME_MAX_LENGTH = (255 * 255) - 1;

    /// Extra field can not be longer than this (the length field has only 2 bytes)
    const EXTRA_FIELD_MAX_LENGTH = (255 * 255) - 1;

    /**
     * @var int
     */
    private $versionNeededToExtract;

    /**
     * @var int
     */
    private $generalPurposeBitFlags;

    /**
     * @var int
     */
    private $compressionMethod;

    /**
     * @var int
     */
    private $lastModificationFileDate;

    /**
     * @var int
     */
    private $lastModificationFileTime;

    /**
     * @var \DateTimeInterface
     */
    private $lastModification;

    /**
     * @var int
     */
    private $crc32;

    /**
     * @var int
     */
    private $compressedSize;

    /**
     * @var int
     */
    private $uncompressedSize;

    /**
     * @var int
     */
    private $fileNameLength;

    /**
     * @var int
     */
    private $extraFieldLength;

    /**
     * @var string
     */
    private $fileName = "";

    /**
     * @var string
     */
    private $extraField = "";

    /**
     * @var bool
     */
    private $requireAdditionalData = false;

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
        $this->crc32 = $crc32;
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
     * Create the binary on disk representation
     *
     * @return string
     */
    public function marshal() : string
    {
        return \pack(
                'NvvvvvVVVvv',
                self::SIGNATURE,
                $this->versionNeededToExtract,
                $this->generalPurposeBitFlags,
                $this->compressionMethod,
                $this->lastModificationFileTime,
                $this->lastModificationFileDate,
                $this->crc32,
                $this->compressedSize,
                $this->uncompressedSize,
                \strlen($this->fileName),
                \strlen($this->extraField)
            )
            . $this->fileName
            . $this->extraField
            ;
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
            ($offset ? \substr($input, $offset) : $input)
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

    /**
     * Initialize a new local file header from the supplied archive entry object
     *
     * @param ArchiveEntry $archiveEntry
     * @return LocalFileHeader
     */
    public static function createFromArchiveEntry(ArchiveEntry $archiveEntry) : self
    {
        list($modificationTime, $modificationDate) = dateTime2Dos($archiveEntry->getModificationTime());

        return new self(
            0,
            0,
            COMPRESSION_METHOD_REVERSE_MAPPING[$archiveEntry->getTargetCompressionMethod()],
            $modificationTime,
            $modificationDate,
            $archiveEntry->getChecksumCrc32(),
            $archiveEntry->getTargetSize(),
            $archiveEntry->getUncompressedSize(),
            $archiveEntry->getName(),
            null
        );
    }

    /**
     * The number of bytes the fields with variable length require.
     *
     * @return int
     */
    public function getVariableLength(): int
    {
        return $this->fileNameLength + $this->extraFieldLength;
    }

    /**
     * @return int
     */
    public function getVersionNeededToExtract(): int
    {
        return $this->versionNeededToExtract;
    }

    /**
     * @param int $versionNeededToExtract
     * @return LocalFileHeader
     */
    public function setVersionNeededToExtract(int $versionNeededToExtract): LocalFileHeader
    {
        $obj = clone $this;
        $obj->versionNeededToExtract = $versionNeededToExtract;
        return $obj;
    }

    /**
     * @return int
     */
    public function getGeneralPurposeBitFlags(): int
    {
        return $this->generalPurposeBitFlags;
    }

    /**
     * @param int $generalPurposeBitFlags
     * @return LocalFileHeader
     */
    public function setGeneralPurposeBitFlags(int $generalPurposeBitFlags): LocalFileHeader
    {
        $obj = clone $this;
        $obj->generalPurposeBitFlags = $generalPurposeBitFlags;
        return $obj;
    }

    /**
     * @return int
     */
    public function getCompressionMethod(): int
    {
        return $this->compressionMethod;
    }

    /**
     * @param int $compressionMethod
     * @return LocalFileHeader
     */
    public function setCompressionMethod(int $compressionMethod): LocalFileHeader
    {
        $obj = clone $this;
        $obj->compressionMethod = $compressionMethod;
        return $obj;
    }

    /**
     * @return int
     */
    public function getLastModificationFileDate(): int
    {
        return $this->lastModificationFileDate;
    }

    /**
     * @param int $lastModificationFileDate
     * @return LocalFileHeader
     */
    public function setLastModificationFileDate(int $lastModificationFileDate): LocalFileHeader
    {
        $obj = clone $this;
        $obj->lastModificationFileDate = $lastModificationFileDate;
        return $obj;
    }

    /**
     * @return int
     */
    public function getLastModificationFileTime(): int
    {
        return $this->lastModificationFileTime;
    }

    /**
     * @param int $lastModificationFileTime
     * @return LocalFileHeader
     */
    public function setLastModificationFileTime(int $lastModificationFileTime): LocalFileHeader
    {
        $obj = clone $this;
        $obj->lastModificationFileTime = $lastModificationFileTime;
        return $obj;
    }

    /**
     * @return \DateTimeInterface
     * @throws \Exception
     */
    public function getLastModification(): \DateTimeInterface
    {
        return dos2DateTime($this->lastModificationFileTime, $this->lastModificationFileDate);
    }

    /**
     * @param \DateTimeInterface $lastModification
     * @return LocalFileHeader
     */
    public function setLastModification(\DateTimeInterface $lastModification): LocalFileHeader
    {
        $obj = clone $this;
        list($obj->lastModificationFileTime, $obj->lastModificationFileDate) = dateTime2Dos($lastModification);
        return $obj;
    }

    /**
     * @return int
     */
    public function getCrc32(): int
    {
        return $this->crc32;
    }

    /**
     * @param int $crc32
     * @return LocalFileHeader
     */
    public function setCrc32(int $crc32): LocalFileHeader
    {
        $obj = clone $this;
        $obj->crc32 = $crc32;
        return $obj;
    }

    /**
     * @return int
     */
    public function getCompressedSize(): int
    {
        return $this->compressedSize;
    }

    /**
     * @param int $compressedSize
     * @return LocalFileHeader
     */
    public function setCompressedSize(int $compressedSize): LocalFileHeader
    {
        $obj = clone $this;
        $obj->compressedSize = $compressedSize;
        return $obj;
    }

    /**
     * @return int
     */
    public function getUncompressedSize(): int
    {
        return $this->uncompressedSize;
    }

    /**
     * @param int $uncompressedSize
     * @return LocalFileHeader
     */
    public function setUncompressedSize(int $uncompressedSize): LocalFileHeader
    {
        $obj = clone $this;
        $obj->uncompressedSize = $uncompressedSize;
        return $obj;
    }

    /**
     * @return int
     */
    public function getFileNameLength(): int
    {
        return $this->fileNameLength;
    }

    /**
     * @return int
     */
    public function getExtraFieldLength(): int
    {
        return $this->extraFieldLength;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     * @return LocalFileHeader
     */
    public function setFileName(string $fileName): LocalFileHeader
    {
        $obj = clone $this;
        $obj->fileName = $fileName;
        $obj->fileNameLength = \strlen($fileName);
        return $obj;
    }

    /**
     * @return string
     */
    public function getExtraField(): string
    {
        return $this->extraField;
    }

    /**
     * @param string $extraField
     * @return LocalFileHeader
     */
    public function setExtraField(string $extraField): LocalFileHeader
    {
        $obj = clone $this;
        $obj->extraField = $extraField;
        $obj->extraFieldLength = \strlen($extraField);
        return $obj;
    }
}
