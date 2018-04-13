<?php

/*
 * (c) 2018 Dennis Birkholz <dennis@birkholz.org>
 *
 * $Id$
 * Author:    $Format:%an <%ae>, %ai$
 * Committer: $Format:%cn <%ce>, %cI$
 */

namespace morgue\archive;

/**
 * This class represents a single entry in an archive (file, directory)
 *
 * @author Dennis Birkholz <dennis@birkholz.org>
 */
final class ArchiveEntry
{
    const UNIX_ATTRIBUTES_DEFAULT           = UNIX_ATTRIBUTE_USER_READ|UNIX_ATTRIBUTE_USER_WRITE|UNIX_ATTRIBUTE_GROUP_READ|UNIX_ATTRIBUTE_OTHER_READ;
    const UNIX_ATTRIBUTES_DEFAULT_FILE      = self::UNIX_ATTRIBUTES_DEFAULT|UNIX_ATTRIBUTE_TYPE_FILE;
    const UNIX_ATTRIBUTES_DEFAULT_DIRECTORY = self::UNIX_ATTRIBUTES_DEFAULT|UNIX_ATTRIBUTE_TYPE_DIRECTORY|UNIX_ATTRIBUTE_USER_EXECUTE|UNIX_ATTRIBUTE_GROUP_EXECUTE|UNIX_ATTRIBUTE_OTHER_EXECUTE;

    /**
     * The name of this entry, relative to the archive root or absolute/fully qualified
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $uncompressedSize;

    /**
     * @var int
     */
    private $sourceSize;

    /**
     * @var int
     */
    private $targetSize;

    /**
     * @var \DateTimeInterface
     */
    private $creationTime;

    /**
     * @var \DateTimeInterface
     */
    private $modificationTime;

    /**
     * CRC32 checksum of the uncompressed file
     * @var int
     */
    private $checksumCrc32;

    /**
     * @var string
     */
    private $comment;

    /**
     * One of the COMPRESSION_METHOD_* constants
     * @var string
     */
    private $sourceCompressionMethod;

    /**
     * One of the COMPRESSION_METHOD_* constants
     * @var string
     */
    private $targetCompressionMethod;

    /**
     * Combination of DOS_ATTRIBUTE_* flags
     * May be unsupported by target file format
     * @var int
     */
    private $dosAttributes;

    /**
     * Combination of UNIX_ATTRIBUTE_* flags
     * May be unsupported by target file format
     * @var int
     */
    private $unixAttributes;

    /**
     * A PHP stream to the underlying file
     * The stream should not perform any translation (like decompression)
     *  so reading from this stream should yield data in the format
     *  specified by $sourceCompressionMethod
     * @var resource
     */
    private $sourceStream;

    /**
     * Fully qualified path to the underlying uncompressed file
     * @var string
     */
    private $sourcePath;

    /**
     * Binary string containing the content of this entry
     * @var string
     */
    private $sourceString;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Import data from the supplied $stat array. Format is the one returned by stat()/fstat()
     * @param array $stat
     */
    private function importStat(array $stat)
    {
        if (!empty($stat['mode']) && $this->unixAttributes === null) {
            $this->unixAttributes = $stat['mode'];
        }

        $timezone = new \DateTimeZone(\date_default_timezone_get());

        if (!empty($stat['ctime']) && $this->creationTime === null) {
            $this->creationTime = (new \DateTimeImmutable('@' . $stat['ctime']))->setTimezone($timezone);
        }

        if (!empty($stat['mtime']) && $this->modificationTime === null) {
            $this->modificationTime = (new \DateTimeImmutable('@' . $stat['mtime']))->setTimezone($timezone);
        }

        if ($this->unixAttributes & UNIX_ATTRIBUTE_TYPE_DIRECTORY) {
            $this->sourceSize = 0;
            $this->targetSize = 0;
            $this->uncompressedSize = 0;
            $this->sourceCompressionMethod = COMPRESSION_METHOD_STORE;
            $this->targetCompressionMethod = COMPRESSION_METHOD_STORE;
        }

        elseif ($this->unixAttributes & UNIX_ATTRIBUTE_TYPE_FILE) {
            $this->sourceSize = $stat['size'];
            if ($this->sourceCompressionMethod === COMPRESSION_METHOD_STORE) {
                $this->uncompressedSize = $this->sourceSize;
            }
        }
    }

    /**
     * @return resource
     */
    public function getSourceAsStream()
    {
        if ($this->sourceStream !== null) {
            return $this->sourceStream;
        }

        elseif ($this->sourcePath !== null) {
            return \fopen($this->sourcePath, 'r');
        }

        else {
            $fp = \fopen('php://memory', 'r+');
            \fwrite($fp, $this->sourceString);
            \fseek($fp, 0);
            return $fp;
        }
    }

    /**
     * @return string|null
     */
    public function getSourcePath()
    {
        return $this->sourcePath;
    }

    /**
     * Set the path of the file that will serve as source for this entry.
     * The file must exist and be readable.
     * You may provide a URL if a matching stream wrapper is registered with stat support.
     * Otherwise, use withSourceStream() to add a stream directly.
     *
     * @param string $path
     * @param string|null $compressionMethod
     * @return ArchiveEntry
     */
    public function withSourcePath(string $path, string $compressionMethod = null) : self
    {
        if (!\file_exists($path) || !\is_readable($path)) {
            throw new \InvalidArgumentException('Can not use non-existing or unreadable file as source.');
        }

        if ($this->sourcePath !== null || $this->sourceStream !== null || $this->sourceString !== null) {
            throw new \InvalidArgumentException('Can not replace source, create a new entry instead.');
        }

        $obj = clone $this;
        $obj->sourcePath = $path;

        if ($compressionMethod !== null) {
            $obj->sourceCompressionMethod = $compressionMethod;
        }

        if ($compressionMethod === COMPRESSION_METHOD_STORE) {
            $obj->uncompressedSize = $obj->sourceSize;
        }

        if (($stat = @\stat($path)) !== false) {
            $obj->importStat($stat);
        }

        return $obj;
    }

    /**
     * @return resource|null
     */
    public function getSourceStream()
    {
        return $this->sourceStream;
    }

    /**
     * @param resource $stream
     * @param string $compressionMethod
     * @return ArchiveEntry
     */
    public function withSourceStream($stream, string $compressionMethod = null) : self
    {
        if ($this->sourcePath !== null || $this->sourceStream !== null || $this->sourceString !== null) {
            throw new \InvalidArgumentException('Can not replace source, create a new entry instead.');
        }

        $obj = clone $this;
        $obj->sourcePath = null;
        $obj->sourceStream = $stream;
        $obj->sourceString = null;

        if ($compressionMethod !== null) {
            $obj->sourceCompressionMethod = $compressionMethod;
        }

        if (($stat = @\fstat($stream)) !== false) {
            $obj->importStat($stat);
        }

        return $obj;
    }

    /**
     * @return string|null
     */
    public function getSourceString()
    {
        return $this->sourceString;
    }

    /**
     * @param string $content
     * @param string $compressionMethod
     * @return ArchiveEntry
     */
    public function withSourceString(string $content, string $compressionMethod = null) : self
    {
        if ($this->sourcePath !== null || $this->sourceStream !== null || $this->sourceString !== null) {
            throw new \InvalidArgumentException('Can not replace source, create a new entry instead.');
        }

        $obj = clone $this;
        $obj->sourcePath = null;
        $obj->sourceStream = null;
        $obj->sourceString = $content;
        $obj->sourceSize = \strlen($content);

        if ($compressionMethod !== null) {
            $obj->sourceCompressionMethod = $compressionMethod;
        }

        if ($compressionMethod === COMPRESSION_METHOD_STORE) {
            $obj->uncompressedSize = $obj->sourceSize;
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone(\date_default_timezone_get()));
        if ($obj->creationTime === null) {
            $obj->creationTime = $now;
        }

        if ($obj->modificationTime === null) {
            $obj->modificationTime = $now;
        }

        return $obj;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ArchiveEntry
     */
    public function withName(string $name) : self
    {
        $obj = clone $this;
        $obj->name = $name;
        return $obj;
    }

    /**
     * @return int|null
     */
    public function getUncompressedSize()
    {
        return $this->uncompressedSize;
    }

    /**
     * @param int $uncompressedSize
     * @return ArchiveEntry
     */
    public function withUncompressedSize(int $uncompressedSize) : self
    {
        $obj = clone $this;
        $obj->uncompressedSize = $uncompressedSize;
        return $obj;
    }

    /**
     * @return int|null
     */
    public function getSourceSize()
    {
        return $this->sourceSize;
    }

    /**
     * @param int $sourceSize
     * @return ArchiveEntry
     */
    public function withSourceSize(int $sourceSize) : self
    {
        $obj = clone $this;
        $obj->sourceSize = $sourceSize;
        return $obj;
    }

    /**
     * @return int|null
     */
    public function getTargetSize()
    {
        return $this->targetSize;
    }

    /**
     * @param int $targetSize
     * @return ArchiveEntry
     */
    public function withTargetSize(int $targetSize) : self
    {
        $obj = clone $this;
        $obj->targetSize = $targetSize;
        return $obj;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getCreationTime()
    {
        return $this->creationTime;
    }

    /**
     * @param \DateTimeInterface $creationTime
     * @return ArchiveEntry
     */
    public function withCreationTime(\DateTimeInterface $creationTime) : self
    {
        $obj = clone $this;
        $obj->creationTime = $creationTime;
        return $obj;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getModificationTime()
    {
        return $this->modificationTime;
    }

    /**
     * @param \DateTimeInterface $modificationTime
     * @return ArchiveEntry
     */
    public function withModificationTime(\DateTimeInterface $modificationTime) : self
    {
        $obj = clone $this;
        $obj->modificationTime = $modificationTime;
        return $obj;
    }

    /**
     * @return int|null
     */
    public function getChecksumCrc32()
    {
        return $this->checksumCrc32;
    }

    /**
     * @param int $checksumCrc32
     * @return ArchiveEntry
     */
    public function withChecksumCrc32(int $checksumCrc32) : self
    {
        $obj = clone $this;
        $obj->checksumCrc32 = $checksumCrc32;
        return $obj;
    }

    /**
     * @return string|null
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string|null $comment
     * @return ArchiveEntry
     */
    public function withComment(string $comment = null) : self
    {
        $obj = clone $this;
        $obj->comment = $comment;
        return $obj;
    }

    /**
     * @return string|null
     */
    public function getSourceCompressionMethod()
    {
        return $this->sourceCompressionMethod;
    }

    /**
     * Any of the COMPRESSION_METHOD_* constants
     *
     * @param string $sourceCompressionMethod
     * @return ArchiveEntry
     */
    public function withSourceCompressionMethod($sourceCompressionMethod) : self
    {
        $obj = clone $this;
        $obj->sourceCompressionMethod = $sourceCompressionMethod;
        return $obj;
    }

    /**
     * @return string|null
     */
    public function getTargetCompressionMethod()
    {
        return $this->targetCompressionMethod;
    }

    /**
     * @param string $targetCompressionMethod
     * @return ArchiveEntry
     */
    public function withTargetCompressionMethod($targetCompressionMethod) : self
    {
        $obj = clone $this;
        $obj->targetCompressionMethod = $targetCompressionMethod;
        return $obj;
    }

    /**
     * @return int|null
     */
    public function getDosAttributes()
    {
        return $this->dosAttributes;
    }

    /**
     * @param int $dosAttributes
     * @return ArchiveEntry
     */
    public function withDosAttributes(int $dosAttributes) : self
    {
        $obj = clone $this;
        $obj->dosAttributes = $dosAttributes;
        return $obj;
    }

    /**
     * @return int|null
     */
    public function getUnixAttributes()
    {
        return $this->unixAttributes;
    }

    /**
     * @param int $unixAttributes
     * @return ArchiveEntry
     */
    public function withUnixAttributes(int $unixAttributes) : self
    {
        $obj = clone $this;
        $obj->unixAttributes = $unixAttributes;
        return $obj;
    }
}
