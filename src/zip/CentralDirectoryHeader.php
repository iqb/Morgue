<?php

namespace iqb\zip;

class CentralDirectoryHeader
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
    public $versionMadeBy;

    /**
     * @var int
     */
    public $versionNeededToExtract;

    /**
     * @var int
     */
    public $generalPurposeBitFlags;

    /**
     * @var int
     */
    public $compressionMethod;

    /**
     * @var int
     */
    public $lastModificationFileTime;

    /**
     * @var int
     */
    public $lastModificationFileDate;

    /**
     * @var int
     */
    public $crc32;

    /**
     * @var int
     */
    public $compressedSize;

    /**
     * @var int
     */
    public $uncompressedSize;

    /**
     * @var int
     */
    public $fileNameLength;

    /**
     * @var int
     */
    public $extraFieldLength;

    /**
     * @var int
     */
    public $fileCommentLength;

    /**
     * @var int
     */
    public $diskNumberStart;

    /**
     * @var int
     */
    public $internalFileAttributes;

    /**
     * @var int
     */
    public $externalFileAttributes;

    /**
     * @var int
     */
    public $relativeOffsetOfLocalHeader;

    /**
     * @var string
     */
    public $fileName;

    /**
     * @var string
     */
    public $extraField;

    /**
     * @var string
     */
    public $fileComment;

    /**
     * File system or operating system of encoder.
     * One of the HOST_COMPATIBILITY_* constants.
     * @var int
     */
    public $encodingHost;

    /**
     * Maximum supported version of the encoding software.
     * @var int
     */
    public $encodingVersion;

    /**
     * Required host compatibility to decode.
     * One of the HOST_COMPATIBILITY_* constants.
     * @var int
     */
    public $requiredHost;

    /**
     * Zip format version required to decode.
     * @var int
     */
    public $requiredVersion;

    /**
     * @var \DateTimeInterface
     */
    public $lastModification;

    /**
     * @var int
     */
    public $dosExternalAttributes;

    /**
     * @var int
     */
    public $unixExternalAttributes;

    /**
     * @var int
     */
    public $requireAdditionalData;


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


    public static function parse(string $input, int $offset = 0)
    {
        if (\strlen($input) < ($offset+self::MIN_LENGTH)) {
            throw new \InvalidArgumentException("Not enough data to parse central directory header!");
        }

        $parsed = \unpack(
            'Nsignature/'
            . 'vversionMadeBy'
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
            $input,
            $offset
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
        $centralDirectoryHeader->requireAdditionalData = $centralDirectoryHeader->fileNameLength + $centralDirectoryHeader->extraFieldLength + $centralDirectoryHeader->fileCommentLength;

        return $centralDirectoryHeader;
    }


    /**
     * After a new object has been created by parse(), this method must be called to initialize the file name, extra field and file comment entries which have dynamic field length.
     * The required number of bytes is written to the $requireAdditionalData attribute by parse().
     *
     * @param string $input
     * @param int $offset
     * @return int
     */
    public function parseAdditionalData(string $input, int $offset = 0) : int
    {
        if ($this->fileName !== null || $this->extraField !== null || $this->fileComment !== null) {
            throw new \BadMethodCallException("Additional data already parsed!");
        }

        if (!$this->requireAdditionalData) {
            throw new \BadMethodCallException("No additional data required!");
        }

        if (\strlen($input) < ($offset + $this->fileNameLength + $this->extraFieldLength + $this->fileCommentLength)) {
            throw new \InvalidArgumentException("Not enough input to parse additional data!");
        }

        $this->fileName = \substr($input, $offset, $this->fileNameLength);
        $this->extraField = bin2hex(\substr($input, $offset+$this->fileNameLength, $this->extraFieldLength));
        $this->fileComment = \substr($input, $offset+$this->fileNameLength+$this->extraFieldLength, $this->fileCommentLength);
        $this->requireAdditionalData = null;

        return $this->fileNameLength + $this->extraFieldLength + $this->fileCommentLength;
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
