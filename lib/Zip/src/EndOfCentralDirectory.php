<?php

namespace morgue\zip;

final class EndOfCentralDirectory
{
    const SIGNATURE = 0x504b0506;

    /// Minimum length of this entry if zip file comment is empty
    const MIN_LENGTH = 22;

    /// Maximum length of this entry if zip file comment has the maximum length
    const MAX_LENGTH = self::MIN_LENGTH + self::ZIP_FILE_COMMENT_MAX_LENGTH;

    /// The zip file comment can not be longer than this (the length field has only 2 bytes)
    const ZIP_FILE_COMMENT_MAX_LENGTH = (255 * 255) - 1;

    /**
     * @var int
     */
    private $numberOfThisDisk;

    /**
     * @var int
     */
    private $numberOfTheDiskWithTheStartOfTheCentralDirectory;

    /**
     * @var int
     */
    private $totalNumberOfEntriesInTheCentralDirectoryOnThisDisk;

    /**
     * @var int
     */
    private $totalNumberOfEntriesInTheCentralDirectory;

    /**
     * @var int
     */
    private $sizeOfTheCentralDirectory;

    /**
     * @var int
     */
    private $offsetOfStartOfCentralDirectoryWithRespectToTheStartingDiskNumber;

    /**
     * @var int
     */
    private $zipFileCommentLength;

    /**
     * @var string
     */
    private $zipFileComment = "";

    /**
     * @var bool
     */
    private $requireAdditionalData = false;

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
     * Use getVariableLength() to get the required number of bytes to execute parseAdditionalData().
     *
     * @param string $input
     * @param int $offset Start at this position inside the string
     * @return static
     */
    public static function parse(string $input, int $offset = 0)
    {
        if (\strlen($input) < ($offset+self::MIN_LENGTH)) {
            throw new \InvalidArgumentException("Not enough data to parse end of central directory!");
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
            throw new \InvalidArgumentException("Invalid signature for end of central directory!");
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
        $endOfCentralDirectory->requireAdditionalData = ($endOfCentralDirectory->zipFileCommentLength > 0);

        return $endOfCentralDirectory;
    }

    /**
     * After a new object has been created by parse(), this method must be called to initialize the zip file comment entry which has variable field length.
     * The required number of bytes can be obtained by getVariableLength()
     *
     * @param string $input
     * @param int $offset
     * @return int Consumed bytes, equals getVariableLength()
     */
    public function parseAdditionalData(string $input, int $offset = 0) : int
    {
        if (!$this->requireAdditionalData) {
            throw new \BadMethodCallException("No additional data required!");
        }

        if (\strlen($input) < ($offset + $this->zipFileCommentLength)) {
            throw new \InvalidArgumentException("Not enough input to parse additional data!");
        }

        $this->zipFileComment = \substr($input, $offset, $this->zipFileCommentLength);
        $this->requireAdditionalData = false;

        return $this->zipFileCommentLength;
    }

    /**
     * Create the binary on disk representation
     *
     * @return string
     */
    public function marshal() : string
    {
        return \pack(
                'NvvvvVVv',
                self::SIGNATURE,
                $this->numberOfThisDisk,
                $this->numberOfTheDiskWithTheStartOfTheCentralDirectory,
                $this->totalNumberOfEntriesInTheCentralDirectoryOnThisDisk,
                $this->totalNumberOfEntriesInTheCentralDirectory,
                $this->sizeOfTheCentralDirectory,
                $this->offsetOfStartOfCentralDirectoryWithRespectToTheStartingDiskNumber,
                \strlen($this->zipFileComment)
            )
            . $this->zipFileComment
            ;
    }

    /**
     * The number of bytes the fields with variable length require.
     *
     * @return int
     */
    public function getVariableLength(): int
    {
        return $this->zipFileCommentLength;
    }

    /**
     * @return int
     */
    public function getNumberOfThisDisk(): int
    {
        return $this->numberOfThisDisk;
    }

    /**
     * @param int $numberOfThisDisk
     * @return EndOfCentralDirectory
     */
    public function setNumberOfThisDisk(int $numberOfThisDisk): EndOfCentralDirectory
    {
        $obj = clone $this;
        $obj->numberOfThisDisk = $numberOfThisDisk;
        return $obj;
    }

    /**
     * @return int
     */
    public function getNumberOfTheDiskWithTheStartOfTheCentralDirectory(): int
    {
        return $this->numberOfTheDiskWithTheStartOfTheCentralDirectory;
    }

    /**
     * @param int $numberOfTheDiskWithTheStartOfTheCentralDirectory
     * @return EndOfCentralDirectory
     */
    public function setNumberOfTheDiskWithTheStartOfTheCentralDirectory(int $numberOfTheDiskWithTheStartOfTheCentralDirectory): EndOfCentralDirectory
    {
        $obj = clone $this;
        $obj->numberOfTheDiskWithTheStartOfTheCentralDirectory = $numberOfTheDiskWithTheStartOfTheCentralDirectory;
        return $obj;
    }

    /**
     * @return int
     */
    public function getTotalNumberOfEntriesInTheCentralDirectoryOnThisDisk(): int
    {
        return $this->totalNumberOfEntriesInTheCentralDirectoryOnThisDisk;
    }

    /**
     * @param int $totalNumberOfEntriesInTheCentralDirectoryOnThisDisk
     * @return EndOfCentralDirectory
     */
    public function setTotalNumberOfEntriesInTheCentralDirectoryOnThisDisk(int $totalNumberOfEntriesInTheCentralDirectoryOnThisDisk): EndOfCentralDirectory
    {
        $obj = clone $this;
        $obj->totalNumberOfEntriesInTheCentralDirectoryOnThisDisk = $totalNumberOfEntriesInTheCentralDirectoryOnThisDisk;
        return $obj;
    }

    /**
     * @return int
     */
    public function getTotalNumberOfEntriesInTheCentralDirectory(): int
    {
        return $this->totalNumberOfEntriesInTheCentralDirectory;
    }

    /**
     * @param int $totalNumberOfEntriesInTheCentralDirectory
     * @return EndOfCentralDirectory
     */
    public function setTotalNumberOfEntriesInTheCentralDirectory(int $totalNumberOfEntriesInTheCentralDirectory): EndOfCentralDirectory
    {
        $obj = clone $this;
        $obj->totalNumberOfEntriesInTheCentralDirectory = $totalNumberOfEntriesInTheCentralDirectory;
        return $obj;
    }

    /**
     * @return int
     */
    public function getSizeOfTheCentralDirectory(): int
    {
        return $this->sizeOfTheCentralDirectory;
    }

    /**
     * @param int $sizeOfTheCentralDirectory
     * @return EndOfCentralDirectory
     */
    public function setSizeOfTheCentralDirectory(int $sizeOfTheCentralDirectory): EndOfCentralDirectory
    {
        $obj = clone $this;
        $obj->sizeOfTheCentralDirectory = $sizeOfTheCentralDirectory;
        return $obj;
    }

    /**
     * @return int
     */
    public function getOffsetOfStartOfCentralDirectoryWithRespectToTheStartingDiskNumber(): int
    {
        return $this->offsetOfStartOfCentralDirectoryWithRespectToTheStartingDiskNumber;
    }

    /**
     * @param int $offsetOfStartOfCentralDirectoryWithRespectToTheStartingDiskNumber
     * @return EndOfCentralDirectory
     */
    public function setOffsetOfStartOfCentralDirectoryWithRespectToTheStartingDiskNumber(int $offsetOfStartOfCentralDirectoryWithRespectToTheStartingDiskNumber): EndOfCentralDirectory
    {
        $obj = clone $this;
        $obj->offsetOfStartOfCentralDirectoryWithRespectToTheStartingDiskNumber = $offsetOfStartOfCentralDirectoryWithRespectToTheStartingDiskNumber;
        return $obj;
    }

    /**
     * @return int
     */
    public function getZipFileCommentLength(): int
    {
        return $this->zipFileCommentLength;
    }

    /**
     * @return string
     */
    public function getZipFileComment(): string
    {
        return $this->zipFileComment;
    }

    /**
     * @param string $zipFileComment
     * @return EndOfCentralDirectory
     */
    public function setZipFileComment(string $zipFileComment): EndOfCentralDirectory
    {
        $obj = clone $this;
        $obj->zipFileComment = $zipFileComment;
        $obj->zipFileCommentLength = \strlen($zipFileComment);
        return $obj;
    }
}
