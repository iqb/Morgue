<?php

namespace iqb\zip;

class EndOfCentralDirectory
{
    const SIGNATURE = 0x504b0506;

    /// Minimum length of this entry if zip file comment is empty
    const MIN_LENGTH = 22;

    /// Maximum length of this entry if zip file comment has the maximum length
    const MAX_LENGTH = self::MIN_LENGTH + self::ZIP_FILE_COMMENT_MAX_LENGTH;

    /// The zip file comment can not be longer than this (the length field has only 2 bytes)
    const ZIP_FILE_COMMENT_MAX_LENGTH = (255 * 255) - 1;

    public $numberOfThisDisk;

    public $numberOfTheDiskWithTheStartOfTheCentralDirectory;

    public $totalNumberOfEntriesInTheCentralDirectoryOnThisDisk;

    public $totalNumberOfEntriesInTheCentralDirectory;

    public $sizeOfTheCentralDirectory;

    public $offsetOfStartOfCentralDirectoryWithRespectToTheStartingDiskNumber;

    public $zipFileCommentLength;

    public $zipFileComment;

    /// @var int
    public $requireAdditionalData;


    public function __construct(
        int $numberOfThisDisk,
        int $numberOfTheDiskWithTheStartOfTheCentralDirectory,
        int $totalNumberOfEntriesInTheCentralDirectoryOnThisDisk,
        int $totalNumberOfEntriesInTheCentralDirectory,
        int $sizeOfTheCentralDirectory,
        int $offsetOfStartOfCentralDirectoryWithRespectToTheStartingDiskNumber,
        string $zipFileComment = null
    ) {
        $this->numberOfThisDisk = $numberOfThisDisk;
        $this->numberOfTheDiskWithTheStartOfTheCentralDirectory = $numberOfTheDiskWithTheStartOfTheCentralDirectory;
        $this->totalNumberOfEntriesInTheCentralDirectoryOnThisDisk = $totalNumberOfEntriesInTheCentralDirectoryOnThisDisk;
        $this->totalNumberOfEntriesInTheCentralDirectory = $totalNumberOfEntriesInTheCentralDirectory;
        $this->sizeOfTheCentralDirectory = $sizeOfTheCentralDirectory;
        $this->offsetOfStartOfCentralDirectoryWithRespectToTheStartingDiskNumber = $offsetOfStartOfCentralDirectoryWithRespectToTheStartingDiskNumber;

        if ($zipFileComment !== null) {
            $this->zipFileComment = $zipFileComment;
            $this->zipFileCommentLength = \strlen($this->zipFileComment);
        }
    }


    /**
     * Parse the end of central directory from a binary string.
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
            . '/vnumberOfThisDisk'
            . '/vnumberOfTheDiskWithTheStartOfTheCentralDirectory'
            . '/vtotalNumberOfEntriesInTheCentralDirectoryOnThisDisk'
            . '/vtotalNumberOfEntriesInTheCentralDirectory'
            . '/VsizeOfTheCentralDirectory'
            . '/VoffsetOfStartOfCentralDirectoryWithRespectToTheStartingDiskNumber'
            . '/vzipFileCommentLength',
            ($offset ? \substr($input, $offset) : $input)
        );
        if ($parsed['signature'] !== self::SIGNATURE) {
            throw new \InvalidArgumentException("Invalid signature for local file header!");
        }

        $endOfCentralDirectory = new static(
            $parsed['numberOfThisDisk'],
            $parsed['numberOfTheDiskWithTheStartOfTheCentralDirectory'],
            $parsed['totalNumberOfEntriesInTheCentralDirectoryOnThisDisk'],
            $parsed['totalNumberOfEntriesInTheCentralDirectory'],
            $parsed['sizeOfTheCentralDirectory'],
            $parsed['offsetOfStartOfCentralDirectoryWithRespectToTheStartingDiskNumber']
        );
        $endOfCentralDirectory->zipFileCommentLength = $parsed['zipFileCommentLength'];
        $endOfCentralDirectory->requireAdditionalData = $endOfCentralDirectory->zipFileCommentLength;

        return $endOfCentralDirectory;
    }


    /**
     * After a new object has been created by parse(), this method must be called to initialize the zip file comment entry which has dynamic field length.
     * The required number of bytes is written to the $requireAdditionalData attribute by parse().
     *
     * @param string $input
     * @param int $offset
     * @return int
     */
    public function parseAdditionalData(string $input, int $offset = 0) : int
    {
        if ($this->zipFileComment !== null) {
            throw new \BadMethodCallException("Additional data already parsed!");
        }

        if (!$this->requireAdditionalData) {
            throw new \BadMethodCallException("No additional data required!");
        }

        if (\strlen($input) < ($offset + $this->zipFileCommentLength)) {
            throw new \InvalidArgumentException("Not enough input to parse additional data!");
        }

        $this->zipFileComment = \substr($input, $offset, $this->zipFileCommentLength);
        $this->requireAdditionalData = null;

        return $this->zipFileCommentLength;
    }
}