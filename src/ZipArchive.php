<?php

namespace iqb;

use const iqb\stream\SUBSTREAM_SCHEME;
use morgue\zip\CentralDirectoryHeader;
use morgue\zip\EndOfCentralDirectory;
use morgue\zip\LocalFileHeader;
use const morgue\zip\ZIP_COMPRESSION_METHOD_BZIP2;
use const morgue\zip\ZIP_COMPRESSION_METHOD_DEFLATE;
use const morgue\zip\ZIP_COMPRESSION_METHOD_DEFLATE64;
use const morgue\zip\ZIP_COMPRESSION_METHOD_IMPLODE;
use const morgue\zip\ZIP_COMPRESSION_METHOD_LZ77;
use const morgue\zip\ZIP_COMPRESSION_METHOD_LZMA;
use const morgue\zip\ZIP_COMPRESSION_METHOD_PKWARE_IMPLODE;
use const morgue\zip\ZIP_COMPRESSION_METHOD_PPMD;
use const morgue\zip\ZIP_COMPRESSION_METHOD_REDUCE_1;
use const morgue\zip\ZIP_COMPRESSION_METHOD_REDUCE_2;
use const morgue\zip\ZIP_COMPRESSION_METHOD_REDUCE_3;
use const morgue\zip\ZIP_COMPRESSION_METHOD_REDUCE_4;
use const morgue\zip\ZIP_COMPRESSION_METHOD_SHRINK;
use const morgue\zip\ZIP_COMPRESSION_METHOD_STORE;
use const morgue\zip\ZIP_COMPRESSION_METHOD_TERSE;
use const morgue\zip\ZIP_COMPRESSION_METHOD_TOKENIZE;
use const morgue\zip\ZIP_COMPRESSION_METHOD_WAVPACK;

/**
 *
 * @property-read int $status Status of the Zip Archive
 * @property-read int statusSys System status of the Zip Archive
 * @property-read int numFiles Number of files in the archive
 * @property-read string $comment Comment for the archive
 * @property-read string $filename File name in the file system
 *
 * @link http://php.net/manual/en/class.ziparchive.php
 * @link http://php.net/manual/en/zip.constants.php
 */
class ZipArchive implements \Countable
{
    // Flags for open

    /**
     * Create the archive if it does not exist
     */
    const CREATE = 1;

    /**
     * Error if archive already exists
     */
    const EXCL = 2;

    /**
     * Perform additional consistency checks on the archive, and error if they fail
     */
    const CHECKCONS = 4;

    /**
     * Always start a new archive, this mode will overwrite the file if it already exists
     */
    const OVERWRITE = 8;

    // Generic flags

    /**
     * Ignore case on name lookup
     */
    const FL_NOCASE = 1;

    /**
     * Ignore directory component
     */
    const FL_NODIR = 2;

    /**
     * Read compressed data
     */
    const FL_COMPRESSED = 4;

    /**
     * Use original data, ignoring changes.
     */
    const FL_UNCHANGED = 8;

    // Encoding flags

    /**
     * Guess string encoding (is default)
     */
    const FL_ENC_GUESS = 0;

    /**
     * Get unmodified string
     */
    const FL_ENC_RAW = 64;

    /**
     * Follow specification strictly
     */
    const FL_ENC_STRICT = 128;

    /**
     * String is UTF-8 encoded
     */
    const FL_ENC_UTF_8 = 2048;

    /**
     * String is CP437 encoded
     */
    const FL_ENC_CP437 = 4096;

    // Compression methods

    /**
     * Better of store or deflate
     */
    const CM_DEFAULT = -1;

    const CM_STORE          = ZIP_COMPRESSION_METHOD_STORE;
    const CM_SHRINK         = ZIP_COMPRESSION_METHOD_SHRINK;
    const CM_REDUCE_1       = ZIP_COMPRESSION_METHOD_REDUCE_1;
    const CM_REDUCE_2       = ZIP_COMPRESSION_METHOD_REDUCE_2;
    const CM_REDUCE_3       = ZIP_COMPRESSION_METHOD_REDUCE_3;
    const CM_REDUCE_4       = ZIP_COMPRESSION_METHOD_REDUCE_4;
    const CM_IMPLODE        = ZIP_COMPRESSION_METHOD_IMPLODE;
    const CM_TOKENIZE       = ZIP_COMPRESSION_METHOD_TOKENIZE;
    const CM_DEFLATE        = ZIP_COMPRESSION_METHOD_DEFLATE;
    const CM_DEFLATE64      = ZIP_COMPRESSION_METHOD_DEFLATE64;
    const CM_PKWARE_IMPLODE = ZIP_COMPRESSION_METHOD_PKWARE_IMPLODE;
    const CM_BZIP2          = ZIP_COMPRESSION_METHOD_BZIP2;
    const CM_LZMA           = ZIP_COMPRESSION_METHOD_LZMA;
    const CM_TERSE          = ZIP_COMPRESSION_METHOD_TERSE;
    const CM_LZ77           = ZIP_COMPRESSION_METHOD_LZ77;
    const CM_WAVPACK        = ZIP_COMPRESSION_METHOD_WAVPACK;
    const CM_PPMD           = ZIP_COMPRESSION_METHOD_PPMD;

    // Error constants

    /**
     * No error
     */
    const ER_OK = 0;

    /**
     * Multi-disk zip archives not supported
     */
    const ER_MULTIDISK = 1;

    /**
     * Renaming temporary file failed
     */
    const ER_RENAME = 2;

    /**
     * Closing zip archive failed
     */
    const ER_CLOSE = 3;

    /**
     * Seek error
     */
    const ER_SEEK = 4;

    /**
     * Read error
     */
    const ER_READ = 5;

    /**
     * Write error
     */
    const ER_WRITE = 6;

    /**
     * CRC error
     */
    const ER_CRC = 7;

    /**
     * Containing zip archive was closed
     */
    const ER_ZIPCLOSED = 8;

    /**
     * No such file
     */
    const ER_NOENT = 9;

    /**
     * File already exists
     */
    const ER_EXISTS = 10;

    /**
     * Can't open file
     */
    const ER_OPEN = 11;

    /**
     * Failure to create temporary file
     */
    const ER_TMPOPEN = 12;

    /**
     * Zlib error
     */
    const ER_ZLIB = 13;

    /**
     * Memory allocation failure
     */
    const ER_MEMORY = 14;

    /**
     * Entry has been changed
     */
    const ER_CHANGED = 15;

    /**
     * Compression method not supported
     */
    const ER_COMPNOTSUPP = 16;

    /**
     * Premature EOF
     */
    const ER_EOF = 17;

    /**
     * Invalid argument
     */
    const ER_INVAL = 18;

    /**
     * Not a zip archive
     */
    const ER_NOZIP = 19;

    /**
     * Internal error
     */
    const ER_INTERNAL = 20;

    /**
     * Zip archive inconsistent
     */
    const ER_INCONS = 21;

    /**
     * Can't remove file
     */
    const ER_REMOVE = 22;

    /**
     * Entry has been deleted
     */
    const ER_DELETED = 23;

    // Encryption

    /**
     * No encryption
     */
    const EM_NONE = 0;

    /**
     * AES 128 encryption
     */
    const EM_AES_128 = 257;

    /**
     * AES 192 encryption
     */
    const EM_AES_192 = 258;

    /**
     * AES 256 encryption
     */
    const EM_AES_256 = 259;

    // Operating system constants for external attributes

    /**
     * MS-DOS and OS/2 (FAT / VFAT / FAT32 file systems)
     */
    const OPSYS_DOS = 0;

    /**
     * Amiga
     */
    const OPSYS_AMIGA = 1;

    /**
     * OpenVMS
     */
    const OPSYS_OPENVMS = 2;

    /**
     * UNIX
     */
    const OPSYS_UNIX = 3;

    /**
     * VM/CMS
     */
    const OPSYS_VM_CMS = 4;

    /**
     * Atari ST
     */
    const OPSYS_ATARI_ST = 5;

    /**
     * OS/2 H.P.F.S.
     */
    const OPSYS_OS_2 = 6;

    /**
     * Macintosh
     */
    const OPSYS_MACINTOSH = 7;

    /**
     * Z-System
     */
    const OPSYS_Z_SYSTEM = 8;

    /**
     * CP/M
     */
    const OPSYS_CPM = 9;
    const OPSYS_Z_CPM = self::OPSYS_CPM;

    /**
     * Windows NTFS
     */
    const OPSYS_WINDOWS_NTFS = 10;

    /**
     * MVS (OS/390 - Z/OS)
     */
    const OPSYS_MVS = 11;

    /**
     * VSE
     */
    const OPSYS_VSE = 12;

    /**
     * Acorn Risc
     */
    const OPSYS_ACORN_RISC = 13;

    /**
     * VFAT
     */
    const OPSYS_VFAT = 14;

    /**
     * alternate MVS
     */
    const OPSYS_ALTERNATE_MVS = 15;

    /**
     * BeOS
     */
    const OPSYS_BEOS = 16;

    /**
     * Tandem
     */
    const OPSYS_TANDEM = 17;

    /**
     * OS/400
     */
    const OPSYS_OS_400 = 18;

    /**
     * OS X (Darwin)
     */
    const OPSYS_OS_X = 19;

    const OPSYS_DEFAULT = self::OPSYS_UNIX;

    /**
     * @var bool
     */
    private $bzip2Support = false;

    /**
     * @var bool
     */
    private $deflateSupport = false;

    /**
     * Status of the Zip Archive
     * @var int
     */
    private $status = 0;

    /**
     * System status of the Zip Archive
     * @var int
     */
    private $statusSys = 0;

    /**
     * File name in the file system
     * @var string
     */
    private $filename;

    /**
     * @var resource
     */
    private $handle;

    /**
     * @var CentralDirectoryHeader[]
     */
    private $originalCentralDirectory = [];

    /**
     * @var EndOfCentralDirectory
     */
    private $originalEndOfCentralDirectory;

    /**
     * @var CentralDirectoryHeader[]
     */
    private $modifiedCentralDirectory = [];

    /**
     * @var EndOfCentralDirectory
     */
    private $modifiedEndOfCentralDirectory;


    public function __construct()
    {
        $this->deflateSupport(true);
        $this->bzip2Support(true);
    }


    /**
     * Enable or disable deflate compression support.
     * Can be called without parameter to check status.
     *
     * @param bool $enableSupport Enable or disable deflate compression support
     * @return bool The deflate compression support status valid from now on
     */
    public function deflateSupport(bool $enableSupport = null)
    {
        if ($enableSupport !== null) {
            $this->deflateSupport = ($enableSupport && \in_array('zlib.*', \stream_get_filters()));
        }

        return $this->deflateSupport;
    }


    /**
     * Enable or disable BZip2 compression support.
     * Can be called without parameter to check status.
     *
     * @param bool $enableSupport Enable or disable BZip2 compression support
     * @return bool The BZip2 support status valid from now on
     */
    public function bzip2Support(bool $enableSupport = null)
    {
        if ($enableSupport !== null) {
            $this->bzip2Support = ($enableSupport && \in_array('bzip2.*', \stream_get_filters()));
        }

        return $this->bzip2Support;
    }


    /**
     * Emulate read-only access to class variables
     */
    public function __get(string $name)
    {
        if ($name === 'status') {
            return $this->status;
        } elseif ($name === 'statusSys') {
            return $this->statusSys;
        } elseif ($name === 'filename') {
            return $this->filename;
        } elseif ($name === 'numFiles') {
            return $this->count();
        } elseif ($name === 'comment') {
            return $this->getArchiveComment();
        }
    }


    /**
     * Emulate writes that vanish into the air for class variables
     */
    public function __set(string $name, $value)
    {
    }


    /**
     * @implements \Countable
     * @return int
     */
    public function count()
    {
        return \count($this->modifiedCentralDirectory);
    }


    public function open(string $filename)
    {
        $this->filename = \realpath($filename);
        $this->handle = \fopen($this->filename, 'r+');

        // Find end of central directory
        $this->originalEndOfCentralDirectory = $this->modifiedEndOfCentralDirectory = $this->findEndOfCentralDirectory();

        // Read central directory
        if (\fseek($this->handle, $this->originalEndOfCentralDirectory->getOffsetOfStartOfCentralDirectoryWithRespectToTheStartingDiskNumber()) === -1) {
            throw new \RuntimeException("Unable to read Central Directory");
        }

        $centralDirectoryData = \fread($this->handle, $this->originalEndOfCentralDirectory->getSizeOfTheCentralDirectory());
        $offset = 0;

        for ($i=0; $i<$this->originalEndOfCentralDirectory->getTotalNumberOfEntriesInTheCentralDirectory(); $i++) {
            $centralDirectoryEntry = CentralDirectoryHeader::parse($centralDirectoryData, $offset);
            $offset += CentralDirectoryHeader::MIN_LENGTH;

            if ($centralDirectoryEntry->getVariableLength() > 0) {
                $offset += $centralDirectoryEntry->parseAdditionalData($centralDirectoryData, $offset);
            }

            $this->originalCentralDirectory[] = $centralDirectoryEntry;
        }

        $this->modifiedCentralDirectory = $this->originalCentralDirectory;
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
            if ($endOfCentralDirectory->getVariableLength() > 0) {
                $additionalData = \fread($this->handle, $endOfCentralDirectory->getVariableLength());
                $endOfCentralDirectory->parseAdditionalData($additionalData);
            }

            return $endOfCentralDirectory;
        }

        return null;
    }


    /**
     * Returns the Zip archive comment
     *
     * @param int $flags ZipArchive::FL_UNCHANGED or 0
     * @return string|bool
     *
     * @link http://php.net/manual/en/ziparchive.getarchivecomment.php
     */
    final public function getArchiveComment(int $flags = null)
    {
        $validFlags = (is_null($flags) ? 0 : $flags & self::FL_UNCHANGED);

        if ($validFlags & self::FL_UNCHANGED) {
            return $this->originalEndOfCentralDirectory->getZipFileComment();
        }

        else {
            return $this->modifiedEndOfCentralDirectory->getZipFileComment();
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
    final public function getCommentIndex(int $index, int $flags = null)
    {
        $validFlags = (is_null($flags) ? 0 : $flags & self::FL_UNCHANGED);
        $directory = ($validFlags & self::FL_UNCHANGED ? $this->originalCentralDirectory : $this->modifiedCentralDirectory);

        if (isset($directory[$index])) {
            $this->status = self::ER_OK;
            return $directory[$index]->getFileComment();
        } else {
            $this->status = self::ER_INVAL;
            return false;
        }
    }


    /**
     * Returns the comment of an entry using the entry name
     *
     * @param string $name Name of the entry
     * @param int $flags Any combination of ZipArchive::FL_UNCHANGED
     * @return string|false
     *
     * @link http://php.net/manual/en/ziparchive.getcommentname.php
     */
    final public function getCommentName(string $name, int $flags = null)
    {
        $validFlags = (is_null($flags) ? 0 : $flags & (self::FL_UNCHANGED));

        if (($index = $this->locateName($name, $validFlags)) !== false) {
            // Hack to align behaviour with \ZipArchive
            if ($flags & self::FL_NODIR) {
                $this->status = self::ER_NOENT;
            }
            return $this->getCommentIndex($index, $validFlags);
        } else {
            return false;
        }
    }


    /**
     * Retrieve the external attributes of an entry defined by its index
     *
     * @param int $index Index of the entry.
     * @param int $opsys On success, receive the operating system code defined by one of the ZipArchive::OPSYS_ constants.
     * @param int $attr On success, receive the external attributes. Value depends on operating system.
     * @param int $flags ZipArchive::FL_UNCHANGED or 0
     * @return bool
     *
     * @link http://php.net/manual/en/ziparchive.getexternalattributesindex.php
     */
    final public function getExternalAttributesIndex(int $index, &$opsys, &$attr, int $flags = null)
    {
        $validFlags = (is_null($flags) ? 0 : $flags & self::FL_UNCHANGED);
        $directory = ($validFlags & self::FL_UNCHANGED ? $this->originalCentralDirectory : $this->modifiedCentralDirectory);

        if (!isset($directory[$index])) {
            return false;
        }

        $opsys = $directory[$index]->getEncodingHost();
        $attr = $directory[$index]->getExternalFileAttributes();

        return true;
    }


    /**
     * Retrieve the external attributes of an entry defined by its name
     *
     * @param string $name Name of the entry.
     * @param int $opsys On success, receive the operating system code defined by one of the ZipArchive::OPSYS_ constants.
     * @param int $attr On success, receive the external attributes. Value depends on operating system.
     * @param int $flags ZipArchive::FL_UNCHANGED or 0
     * @return bool
     *
     * @link http://php.net/manual/en/ziparchive.getexternalattributesname.php
     */
    final public function getExternalAttributesName(string $name, &$opsys, &$attr, int $flags = null)
    {
        $validFlags = (is_null($flags) ? 0 : $flags & (self::FL_UNCHANGED));

        if (($index = $this->locateName($name, $validFlags)) !== false) {
            return $this->getExternalAttributesIndex($index, $opsys, $attr, $flags);
        } else {
            return false;
        }
    }


    /**
     * Returns the entry contents using its index
     *
     * @param int $index Index of the entry
     * @param int $length The length to be read from the entry. If 0, then the entire entry is read.
     * @param int $flags Any combination of ZipArchive::FL_COMPRESSED|ZipArchive::FL_UNCHANGED
     * @return string|false
     *
     * @link http://php.net/manual/en/ziparchive.getfromindex.php
     */
    final public function getFromIndex(int $index, int $length = null, int $flags = null)
    {
        $validFlags = (is_null($flags) ? 0 : $flags & (self::FL_COMPRESSED|self::FL_UNCHANGED));

        if (($stream = $this->getStreamIndex($index, $validFlags)) === false) {
            return false;
        }

        $length = (is_null($length) ? 0 : $length);
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
     * Returns the entry contents using its name
     *
     * @param string $name Name of the entry
     * @param int $length The length to be read from the entry. If 0, then the entire entry is read.
     * @param int $flags Any combination of ZipArchive::FL_COMPRESSED|ZipArchive::FL_NOCASE|ZipArchive::FL_UNCHANGED
     * @return bool
     *
     * @link http://php.net/manual/en/ziparchive.getfromname.php
     */
    final public function getFromName(string $name, int $length = null, int $flags = null)
    {
        $validFlags = (is_null($flags) ? 0 : $flags & (self::FL_COMPRESSED|self::FL_NOCASE|self::FL_NODIR|self::FL_UNCHANGED));

        if (($index = $this->locateName($name, $validFlags)) === false) {
            return false;
        }

        return $this->getFromIndex($index, $length, $validFlags);
    }


    /**
     * @return string
     */
    final public function getStatusString()
    {
        switch ($this->status) {
            case self::ER_OK:
                return "No error";

            case self::ER_NOENT:
                return "No such file";

            case self::ER_COMPNOTSUPP:
                return "Compression method not supported";

            case self::ER_INVAL:
                return "Invalid argument";

            default:
                return "";
        }
    }


    /**
     * Get a file handler to the entry defined by its name (read only)
     *
     * @param string $name The name of the entry to use.
     * @return resource|false
     *
     * @link http://php.net/manual/en/ziparchive.getstream.php
     */
    final public function getStream(string $name)
    {
        return $this->getStreamName($name);
    }


    /**
     * Get a file handle to the entry defined by its index (read only)
     *
     * @param int $index Index of the entry
     * @param int $flags Any combination of ZipArchive::FL_COMPRESSED|ZipArchive::FL_UNCHANGED
     * @return resource|false
     */
    final public function getStreamIndex(int $index, int $flags = null)
    {
        $validFlags = (is_null($flags) ? 0 : $flags & (self::FL_COMPRESSED|self::FL_UNCHANGED));
        $directory = ($validFlags & self::FL_UNCHANGED ? $this->originalCentralDirectory : $this->modifiedCentralDirectory);

        $this->status = self::ER_OK;

        if (!isset($directory[$index])) {
            return false;
        }
        $entry = $directory[$index];

        if ($entry->getCompressedSize() === 0) {
            return \fopen('php://memory', 'r');
        }

        \fseek($this->handle, $entry->getRelativeOffsetOfLocalHeader());
        $localHeader = LocalFileHeader::parse(\fread($this->handle, LocalFileHeader::MIN_LENGTH));
        if ($localHeader->getVariableLength() > 0) {
            $localHeader->parseAdditionalData(\fread($this->handle, $localHeader->getVariableLength()));
        }

        $offset = \ftell($this->handle);
        $length = $entry->getCompressedSize();

        if (($handle = \fopen(SUBSTREAM_SCHEME . '://' . $offset . ':' . $length . '/' . (int)$this->handle, 'r')) === false) {
            return false;
        }

        if (($entry->getCompressionMethod() === self::CM_STORE) || ($validFlags & self::FL_COMPRESSED)) {
            return $handle;
        }

        elseif ($this->deflateSupport && ($entry->getCompressionMethod() === self::CM_DEFLATE)) {
            \stream_filter_append($handle, 'zlib.inflate', \STREAM_FILTER_READ);
            return $handle;
        }

        elseif ($this->bzip2Support && ($entry->getCompressionMethod() === self::CM_BZIP2)) {
            \stream_filter_append($handle, 'bzip2.decompress', \STREAM_FILTER_READ);
            return $handle;
        }

        else {
            $this->status = self::ER_COMPNOTSUPP;
            return false;
        }
    }


    /**
     * Get a file handler to the entry defined by its name (read only)
     *
     * @param string $name The name of the entry to use.
     * @param int|null $flags Any combination of ZipArchive::FL_COMPRESSED|ZipArchive::FL_NOCASE|ZipArchive::FL_NODIR|ZipArchive::FL_UNCHANGED
     * @return resource|false
     */
    public function getStreamName(string $name, int $flags = null)
    {
        $validFlags = (is_null($flags) ? 0 : $flags & (self::FL_COMPRESSED|self::FL_NOCASE|self::FL_NODIR|self::FL_UNCHANGED));

        if (($index = $this->locateName($name, $validFlags)) === false) {
            return false;
        }

        return $this->getStreamIndex($index, $validFlags);
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
        $validFlags = (is_null($flags) ? 0 : $flags & (self::FL_NOCASE|self::FL_NODIR));
        $this->status = self::ER_OK;

        $ignoreCase = (($validFlags & self::FL_NOCASE) !== 0);
        $ignoreDir = (($validFlags & self::FL_NODIR) !== 0);

        $name = ($ignoreCase ? \strtolower($name) : $name);

        foreach ($this->originalCentralDirectory as $possibleIndex => $possibleEntry) {
            if ($ignoreDir && $possibleEntry->isDirectory()) {
                continue;
            }

            $entryName = $possibleEntry->getFileName();
            $entryName = ($ignoreCase ? \strtolower($entryName) : $entryName);
            $entryName = ($ignoreDir ? \basename($entryName) : $entryName);

            if ($name === $entryName) {
                return $possibleIndex;
            }
        }

        $this->status = self::ER_NOENT;
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
    final public function statIndex(int $index, int $flags = null)
    {
        $validFlags = (is_null($flags) ? 0 : $flags & (self::FL_UNCHANGED));

        if (!isset($this->originalCentralDirectory[$index])) {
            return false;
        }

        /* @var $entry CentralDirectoryHeader */
        $entry = $this->originalCentralDirectory[$index];

        return [
            'name' => $entry->getFileName(),
            'index' => $index,
            'crc' => $entry->getCrc32(),
            'size' => $entry->getUncompressedSize(),
            'mtime' => $entry->getLastModification()->getTimestamp(),
            'comp_size' => $entry->getCompressedSize(),
            'comp_method' => $entry->getCompressionMethod(),
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
        $validFlags = (is_null($flags) ? 0 : $flags & (self::FL_NOCASE|self::FL_NODIR|self::FL_UNCHANGED));

        if (($index = $this->locateName($name, $validFlags)) !== false) {
            return $this->statIndex($index, $validFlags);
        }

        return false;
    }
}
