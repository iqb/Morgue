<?php

namespace morgue\zip;

use morgue\archive\ArchiveEntry;

/**
 * This class represents a "central directory header" data structure as defined in the ZIP specification.
 * CentralDirectoryHeader objects are immutable so every setX() method will return a copy with modified fields,
 *  the original object remains unchanged.
 *
 * To create a CentralDirectoryHeader from its binary representation, call the parse() method and supply
 *  CentralDirectoryHeader::MIN_LENGTH bytes to read the fixed size fields.
 * getVariableLength() will yield the number of bytes required to parse the variable size fields.
 * Call parseAdditionalData() with that number of bytes will finalize the object which is immutable from that point on.
 */
final class CentralDirectoryHeader
{
    const SIGNATURE = 0x504b0102;

    /// Minimum length of this entry if neither file name, extra field nor file comment are set
    const MIN_LENGTH = 46;

    /// Maximum length of this entry file name, extra field and file comment have the maximum allowed length
    const MAX_LENGTH = self::MIN_LENGTH + self::FILE_NAME_MAX_LENGTH + self::EXTRA_FIELD_MAX_LENGTH + self::FILE_COMMENT_MAX_LENGTH;

    /// File name can not be longer than this (the length field has only 2 bytes)
    const FILE_NAME_MAX_LENGTH = (255 * 255) - 1;

    /// Extra field can not be longer than this (the length field has only 2 bytes)
    const EXTRA_FIELD_MAX_LENGTH = (255 * 255) - 1;

    /// File comment can not be longer than this (the length field has only 2 bytes)
    const FILE_COMMENT_MAX_LENGTH = (255 * 255) - 1;

    /**
     * @var int
     */
    private $versionMadeBy;

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
    private $lastModificationFileTime;

    /**
     * @var int
     */
    private $lastModificationFileDate;

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
     * @var int
     */
    private $fileCommentLength;

    /**
     * @var int
     */
    private $diskNumberStart;

    /**
     * @var int
     */
    private $internalFileAttributes;

    /**
     * @var int
     */
    private $externalFileAttributes;

    /**
     * @var int
     */
    private $relativeOffsetOfLocalHeader;

    /**
     * @var string
     */
    private $fileName = "";

    /**
     * @var string
     */
    private $extraField = "";

    /**
     * @var string
     */
    private $fileComment = "";

    /**
     * File system or operating system of encoder.
     * One of the HOST_COMPATIBILITY_* constants.
     * @var int
     */
    private $encodingHost;

    /**
     * Maximum supported version of the encoding software.
     * @var int
     */
    private $encodingVersion;

    /**
     * Required host compatibility to decode.
     * One of the HOST_COMPATIBILITY_* constants.
     * @var int
     */
    private $requiredHost;

    /**
     * Zip format version required to decode.
     * @var int
     */
    private $requiredVersion;

    /**
     * @var \DateTimeInterface
     */
    private $lastModification;

    /**
     * @var int
     */
    private $dosExternalAttributes;

    /**
     * @var int
     */
    private $unixExternalAttributes;

    /**
     * @var bool
     */
    private $requireAdditionalData = false;

    /**
     * @var ExtraFieldInterface[]
     */
    private $extraFields = [];

    public function __construct(
        int $versionMadeBy,
        int $versionNeededToExtract,
        int $generalPurposeBitFlags,
        int $compressionMethod,
        int $lastModificationFileTime,
        int $lastModificationFileDate,
        int $crc32,
        int $compressedSize,
        int $uncompressedSize,
        int $diskNumberStart,
        int $internalFileAttributes,
        int $externalFileAttributes,
        int $relativeOffsetOfLocalHeader,
        string $fileName = null,
        string $extraField = null,
        string $fileComment = null
    ) {
        $this->versionMadeBy = $versionMadeBy;
        $this->versionNeededToExtract = $versionNeededToExtract;
        $this->generalPurposeBitFlags = $generalPurposeBitFlags;
        $this->compressionMethod = $compressionMethod;
        $this->lastModificationFileTime = $lastModificationFileTime;
        $this->lastModificationFileDate = $lastModificationFileDate;
        $this->lastModification = dos2DateTime($this->lastModificationFileTime, $this->lastModificationFileDate);
        $this->crc32 = $crc32;
        $this->compressedSize = $compressedSize;
        $this->uncompressedSize = $uncompressedSize;
        $this->diskNumberStart = $diskNumberStart;
        $this->internalFileAttributes = $internalFileAttributes;
        $this->externalFileAttributes = $externalFileAttributes;
        $this->relativeOffsetOfLocalHeader = $relativeOffsetOfLocalHeader;

        if ($fileName !== null) {
            $this->fileName = $fileName;
            $this->fileNameLength = \strlen($fileName);
        }
        if ($extraField !== null) {
            $this->extraField = $extraField;
            $this->extraFieldLength = \strlen($extraField);
        }
        if ($fileComment !== null) {
            $this->fileComment = $fileComment;
            $this->fileCommentLength = \strlen($fileComment);
        }

        $this->encodingHost = ($this->versionMadeBy >> 8);
        $this->encodingVersion = ($this->versionMadeBy & 255);
        $this->requiredHost = ($this->versionNeededToExtract >> 8);
        $this->requiredVersion = ($this->versionNeededToExtract & 255);

        $this->dosExternalAttributes = ($this->externalFileAttributes & 255);

        if ($this->encodingHost === HOST_COMPATIBILITY_UNIX) {
            $this->unixExternalAttributes = ($this->externalFileAttributes >> 16);
        }
    }

    /**
     * Parse the binary representation of a central directory header from $input, optionally start at $offset instead of the beginning of the string.
     * To complete the parsing process, parseAdditionalData() must be called with at least the number of bytes as input as returned by getRequireAdditionalData().
     * After the parseAdditionalData() call the object is immutable.
     *
     * @param string $input
     * @param int $offset
     * @return CentralDirectoryHeader
     */
    public static function parse(string $input, int $offset = 0)
    {
        if (\strlen($input) < ($offset+self::MIN_LENGTH)) {
            throw new \InvalidArgumentException("Not enough data to parse central directory header!");
        }

        $parsed = \unpack(
            'Nsignature'
            . '/vversionMadeBy'
            . '/vversionNeededToExtract'
            . '/vgeneralPurposeBitFlags'
            . '/vcompressionMethod'
            . '/vlastModificationFileTime'
            . '/vlastModificationFileDate'
            . '/Vcrc32'
            . '/VcompressedSize'
            . '/VuncompressedSize'
            . '/vfileNameLength'
            . '/vextraFieldLength'
            . '/vfileCommentLength'
            . '/vdiskNumberStart'
            . '/vinternalFileAttributes'
            . '/VexternalFileAttributes'
            . '/VrelativeOffsetOfLocalHeader',
            ($offset ? \substr($input, $offset) : $input)
        );
        if ($parsed['signature'] !== self::SIGNATURE) {
            throw new \InvalidArgumentException("Invalid signature for central directory header!");
        }

        $centralDirectoryHeader = new static(
            $parsed['versionMadeBy'],
            $parsed['versionNeededToExtract'],
            $parsed['generalPurposeBitFlags'],
            $parsed['compressionMethod'],
            $parsed['lastModificationFileTime'],
            $parsed['lastModificationFileDate'],
            $parsed['crc32'],
            $parsed['compressedSize'],
            $parsed['uncompressedSize'],
            $parsed['diskNumberStart'],
            $parsed['internalFileAttributes'],
            $parsed['externalFileAttributes'],
            $parsed['relativeOffsetOfLocalHeader']
        );
        $centralDirectoryHeader->fileNameLength = $parsed['fileNameLength'];
        $centralDirectoryHeader->extraFieldLength = $parsed['extraFieldLength'];
        $centralDirectoryHeader->fileCommentLength = $parsed['fileCommentLength'];
        $centralDirectoryHeader->requireAdditionalData = ($centralDirectoryHeader->fileNameLength + $centralDirectoryHeader->extraFieldLength + $centralDirectoryHeader->fileCommentLength > 0);

        return $centralDirectoryHeader;
    }

    /**
     * After a new object has been created by parse(), this method must be called to initialize the file name, extra field and file comment entries which have dynamic field length.
     * The required number of bytes is written to the $requireAdditionalData attribute by parse().
     *
     * @param string $input
     * @param int $offset
     * @return int The number of bytes consumed (equals getVariableLength())
     */
    public function parseAdditionalData(string $input, int $offset = 0) : int
    {
        if (!$this->requireAdditionalData) {
            throw new \BadMethodCallException("No additional data required!");
        }

        $variableLength = $this->fileNameLength + $this->extraFieldLength + $this->fileCommentLength;

        if (\strlen($input) < ($offset + $variableLength)) {
            throw new \InvalidArgumentException("Not enough input to parse additional data!");
        }

        $this->fileName = \substr($input, $offset, $this->fileNameLength);
        $offset += $this->fileNameLength;
        $extraField = \substr($input, $offset, $this->extraFieldLength);
        $this->extraField = $extraField;
        $offset += $this->extraFieldLength;
        $this->extraFields = ExtraField::parseAll($extraField, $this);
        $this->fileComment = \substr($input, $offset, $this->fileCommentLength);
        $this->requireAdditionalData = false;

        return $variableLength;
    }

    /**
     * Create the binary on disk representation
     *
     * @return string
     */
    public function marshal() : string
    {
        return \pack(
                'NvvvvvvVVVvvvvvVV',
                self::SIGNATURE,
                $this->versionMadeBy,
                $this->versionNeededToExtract,
                $this->generalPurposeBitFlags,
                $this->compressionMethod,
                $this->lastModificationFileTime,
                $this->lastModificationFileDate,
                $this->crc32,
                $this->compressedSize,
                $this->uncompressedSize,
                \strlen($this->fileName),
                \strlen($this->extraField),
                \strlen($this->fileComment),
                $this->diskNumberStart,
                $this->internalFileAttributes,
                $this->externalFileAttributes,
                $this->relativeOffsetOfLocalHeader
            )
            . $this->fileName
            . $this->extraField
            . $this->fileComment
            ;
    }

    /**
     * Initialize a new central directory header from the supplied archive entry object
     *
     * @param ArchiveEntry $archiveEntry
     * @return CentralDirectoryHeader
     */
    public static function createFromArchiveEntry(ArchiveEntry $archiveEntry) : self
    {
        list($modificationTime, $modificationDate) = dateTime2Dos($archiveEntry->getModificationTime());

        $obj = new self(
            0,
            0,
            0,
            COMPRESSION_METHOD_REVERSE_MAPPING[$archiveEntry->getTargetCompressionMethod()],
            $modificationTime,
            $modificationDate,
            $archiveEntry->getChecksumCrc32(),
            $archiveEntry->getTargetSize(),
            $archiveEntry->getUncompressedSize(),
            0,
            0,
            0,
            0,
            $archiveEntry->getName(),
            null,
            $archiveEntry->getComment()
        );

        return $obj;
    }

    /**
     * Create an archive entry from the data of this central directory header
     *
     * @return ArchiveEntry
     */
    public function toArchiveEntry() : ArchiveEntry
    {
        return (new ArchiveEntry($this->fileName))
            ->withCreationTime($this->lastModification)
            ->withModificationTime($this->lastModification)
            ->withSourceCompressionMethod(COMPRESSION_METHOD_MAPPING[$this->compressionMethod])
            ->withSourceSize($this->compressedSize)
            ->withTargetCompressionMethod(COMPRESSION_METHOD_MAPPING[$this->compressionMethod])
            ->withTargetSize($this->compressedSize)
            ->withChecksumCrc32($this->crc32)
            ->withUncompressedSize($this->uncompressedSize)
            ->withDosAttributes($this->dosExternalAttributes)
            ->withUnixAttributes($this->unixExternalAttributes)
            ->withComment($this->fileComment !== '' ? $this->fileComment : null)
            ;
    }

    /**
     * The number of bytes the fields with variable length require.
     *
     * @return int
     */
    public function getVariableLength(): int
    {
        return $this->fileNameLength + $this->extraFieldLength + $this->fileCommentLength;
    }

    /**
     * @return int
     */
    public function getVersionMadeBy(): int
    {
        return $this->versionMadeBy;
    }

    /**
     * @param int $versionMadeBy
     * @return CentralDirectoryHeader
     */
    public function setVersionMadeBy(int $versionMadeBy): CentralDirectoryHeader
    {
        $obj = clone $this;
        $obj->versionMadeBy = $versionMadeBy;
        return $obj;
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
     * @return CentralDirectoryHeader
     */
    public function setVersionNeededToExtract(int $versionNeededToExtract): CentralDirectoryHeader
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
     * @return CentralDirectoryHeader
     */
    public function setGeneralPurposeBitFlags(int $generalPurposeBitFlags): CentralDirectoryHeader
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
     * @return CentralDirectoryHeader
     */
    public function setCompressionMethod(int $compressionMethod): CentralDirectoryHeader
    {
        $obj = clone $this;
        $obj->compressionMethod = $compressionMethod;
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
     * @return CentralDirectoryHeader
     */
    public function setLastModificationFileTime(int $lastModificationFileTime): CentralDirectoryHeader
    {
        $obj = clone $this;
        $obj->lastModificationFileTime = $lastModificationFileTime;
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
     * @return CentralDirectoryHeader
     */
    public function setLastModificationFileDate(int $lastModificationFileDate): CentralDirectoryHeader
    {
        $obj = clone $this;
        $obj->lastModificationFileDate = $lastModificationFileDate;
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
     * @return CentralDirectoryHeader
     */
    public function setCrc32(int $crc32): CentralDirectoryHeader
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
     * @return CentralDirectoryHeader
     */
    public function setCompressedSize(int $compressedSize): CentralDirectoryHeader
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
     * @return CentralDirectoryHeader
     */
    public function setUncompressedSize(int $uncompressedSize): CentralDirectoryHeader
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
     * @return int
     */
    public function getFileCommentLength(): int
    {
        return $this->fileCommentLength;
    }

    /**
     * @return int
     */
    public function getDiskNumberStart(): int
    {
        return $this->diskNumberStart;
    }

    /**
     * @param int $diskNumberStart
     * @return CentralDirectoryHeader
     */
    public function setDiskNumberStart(int $diskNumberStart): CentralDirectoryHeader
    {
        $obj = clone $this;
        $obj->diskNumberStart = $diskNumberStart;
        return $obj;
    }

    /**
     * @return int
     */
    public function getInternalFileAttributes(): int
    {
        return $this->internalFileAttributes;
    }

    /**
     * @param int $internalFileAttributes
     * @return CentralDirectoryHeader
     */
    public function setInternalFileAttributes(int $internalFileAttributes): CentralDirectoryHeader
    {
        $obj = clone $this;
        $obj->internalFileAttributes = $internalFileAttributes;
        return $obj;
    }

    /**
     * @return int
     */
    public function getExternalFileAttributes(): int
    {
        return $this->externalFileAttributes;
    }

    /**
     * @param int $externalFileAttributes
     * @return CentralDirectoryHeader
     */
    public function setExternalFileAttributes(int $externalFileAttributes): CentralDirectoryHeader
    {
        $obj = clone $this;
        $obj->externalFileAttributes = $externalFileAttributes;
        return $obj;
    }

    /**
     * @return int
     */
    public function getRelativeOffsetOfLocalHeader(): int
    {
        return $this->relativeOffsetOfLocalHeader;
    }

    /**
     * @param int $relativeOffsetOfLocalHeader
     * @return CentralDirectoryHeader
     */
    public function setRelativeOffsetOfLocalHeader(int $relativeOffsetOfLocalHeader): CentralDirectoryHeader
    {
        $obj = clone $this;
        $obj->relativeOffsetOfLocalHeader = $relativeOffsetOfLocalHeader;
        return $obj;
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
     * @return CentralDirectoryHeader
     */
    public function setFileName(string $fileName): CentralDirectoryHeader
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
     * @return CentralDirectoryHeader
     */
    public function setExtraField(string $extraField): CentralDirectoryHeader
    {
        $obj = clone $this;
        $obj->extraField = $extraField;
        $obj->extraFieldLength = \strlen($extraField);
        return $obj;
    }

    /**
     * @return string
     */
    public function getFileComment(): string
    {
        return $this->fileComment;
    }

    /**
     * @param string $fileComment
     * @return CentralDirectoryHeader
     */
    public function setFileComment(string $fileComment): CentralDirectoryHeader
    {
        $obj = clone $this;
        $obj->fileComment = $fileComment;
        $obj->fileCommentLength = \strlen($fileComment);
        return $obj;
    }

    /**
     * @return int
     */
    public function getEncodingHost(): int
    {
        return $this->encodingHost;
    }

    /**
     * @param int $encodingHost
     * @return CentralDirectoryHeader
     */
    public function setEncodingHost(int $encodingHost): CentralDirectoryHeader
    {
        $obj = clone $this;
        $obj->encodingHost = $encodingHost;
        return $obj;
    }

    /**
     * @return int
     */
    public function getEncodingVersion(): int
    {
        return $this->encodingVersion;
    }

    /**
     * @param int $encodingVersion
     * @return CentralDirectoryHeader
     */
    public function setEncodingVersion(int $encodingVersion): CentralDirectoryHeader
    {
        $obj = clone $this;
        $obj->encodingVersion = $encodingVersion;
        return $obj;
    }

    /**
     * @return int
     */
    public function getRequiredHost(): int
    {
        return $this->requiredHost;
    }

    /**
     * @param int $requiredHost
     * @return CentralDirectoryHeader
     */
    public function setRequiredHost(int $requiredHost): CentralDirectoryHeader
    {
        $obj = clone $this;
        $obj->requiredHost = $requiredHost;
        return $obj;
    }

    /**
     * @return int
     */
    public function getRequiredVersion(): int
    {
        return $this->requiredVersion;
    }

    /**
     * @param int $requiredVersion
     * @return CentralDirectoryHeader
     */
    public function setRequiredVersion(int $requiredVersion): CentralDirectoryHeader
    {
        $obj = clone $this;
        $obj->requiredVersion = $requiredVersion;
        return $obj;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getLastModification(): \DateTimeInterface
    {
        return $this->lastModification;
    }

    /**
     * @param \DateTimeInterface $lastModification
     * @return CentralDirectoryHeader
     */
    public function setLastModification(\DateTimeInterface $lastModification): CentralDirectoryHeader
    {
        $obj = clone $this;
        $obj->lastModification = $lastModification;
        return $obj;
    }

    /**
     * @return int
     */
    public function getDosExternalAttributes(): int
    {
        return $this->dosExternalAttributes;
    }

    /**
     * @param int $dosExternalAttributes
     * @return CentralDirectoryHeader
     */
    public function setDosExternalAttributes(int $dosExternalAttributes): CentralDirectoryHeader
    {
        $obj = clone $this;
        $obj->dosExternalAttributes = $dosExternalAttributes;
        return $obj;
    }

    /**
     * @return int|null
     */
    public function getUnixExternalAttributes()
    {
        return $this->unixExternalAttributes;
    }

    /**
     * @param int $unixExternalAttributes
     * @return CentralDirectoryHeader
     */
    public function setUnixExternalAttributes(int $unixExternalAttributes): CentralDirectoryHeader
    {
        $obj = clone $this;
        $obj->unixExternalAttributes = $unixExternalAttributes;
        return $obj;
    }

    /**
     * Whether this entry represents a directory or not
     *
     * @return bool
     */
    public function isDirectory() : bool
    {
        return (($this->dosExternalAttributes & DOS_ATTRIBUTE_DIRECTORY) !== 0);
    }
}
