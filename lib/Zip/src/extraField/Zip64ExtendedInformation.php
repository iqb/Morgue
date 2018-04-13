<?php

namespace morgue\zip\extraField;

use morgue\zip\CentralDirectoryHeader;
use morgue\zip\ExtraField;
use morgue\zip\ExtraFieldInterface;
use const morgue\zip\MAX_INT_16;
use const morgue\zip\MAX_INT_32;

/**
 * see 4.5.3 - Zip64 Extended Information Extra Field
 */
final class Zip64ExtendedInformation implements ExtraFieldInterface
{
    const ID = 0x0001;

    const MIN_SIZE = 4;
    const MAX_SIZE = 32;

    /**
     * Original uncompressed file size
     * @var int
     */
    private $originalSize;

    /**
     * Size of compressed data
     * @var int
     */
    private $compressedSize;

    /**
     * Offset of local header record
     * @var int
     */
    private $relativeHeaderOffset;

    /**
     * Number of the disk on which this file starts
     * @var int
     */
    private $diskStartNumber;

    public function __construct(int $originalSize = null, int $compressedSize = null, int $relativeHeaderOffset = null, int $diskStartNumber = null)
    {
        $this->originalSize = $originalSize;
        $this->compressedSize = $compressedSize;
        $this->relativeHeaderOffset = $relativeHeaderOffset;
        $this->diskStartNumber = $diskStartNumber;
    }

    public static function parse(string $input, $context = null)
    {
        $requiredLength = 0;
        $unpackString = 'vheaderId/vdataLength';

        if ($context instanceof CentralDirectoryHeader) {
            if ($context->getUncompressedSize() === MAX_INT_32) {
                $unpackString .= '/PoriginalSize';
                $requiredLength += 8;
            }

            if ($context->getCompressedSize() === MAX_INT_32) {
                $unpackString .= '/PcompressedSize';
                $requiredLength += 8;
            }

            if ($context->getRelativeOffsetOfLocalHeader() === MAX_INT_32) {
                $unpackString .= '/PrelativeHeaderOffset';
                $requiredLength += 8;
            }

            if ($context->getDiskNumberStart() === MAX_INT_16) {
                $unpackString .= '/VdiskStartNumber';
                $requiredLength += 4;
            }
        }

        if (\strlen($input) !== $requiredLength + ExtraField::MIN_LENGTH) {
            throw new \InvalidArgumentException("Trying to parse '$unpackString' that needs $requiredLength bytes, got only " . (\strlen($input) - ExtraField::MIN_LENGTH));
        }

        $parsed = \unpack($unpackString, $input);

        if ($parsed['headerId'] !== self::ID) {
            throw new \InvalidArgumentException("Invalid header ID!");
        }

        return new self(
            (isset($parsed['originalSize'])         ? $parsed['originalSize']         : null),
            (isset($parsed['compressedSize'])       ? $parsed['compressedSize']       : null),
            (isset($parsed['relativeHeaderOffset']) ? $parsed['relativeHeaderOffset'] : null),
            (isset($parsed['diskStartNumber'])      ? $parsed['diskStartNumber']      : null)
        );
    }

    public function getHeaderId()
    {
        return self::ID;
    }

    public function getDataSize()
    {
        return ($this->originalSize !== null ? 8 : 0)
            + ($this->compressedSize !== null ? 8 : 0)
            + ($this->relativeHeaderOffset !== null ? 8 : 0)
            + ($this->diskStartNumber !== null ? 4 : 0)
        ;
    }

    public function getData()
    {
        return ($this->originalSize !== null ? \pack('P', $this->originalSize) : '')
            . ($this->compressedSize !== null ? \pack('P', $this->compressedSize) : '')
            . ($this->relativeHeaderOffset !== null ? \pack('P', $this->relativeHeaderOffset) : '')
            . ($this->diskStartNumber !== null ? \pack('V', $this->diskStartNumber) : '')
        ;
    }

    /**
     * @return int|null
     */
    public function getOriginalSize()
    {
        return $this->originalSize;
    }

    /**
     * @param int $originalSize
     * @return Zip64ExtendedInformation
     */
    public function setOriginalSize(int $originalSize): Zip64ExtendedInformation
    {
        $obj = clone $this;
        $obj->originalSize = $originalSize;
        return $obj;
    }

    /**
     * @return int|null
     */
    public function getCompressedSize()
    {
        return $this->compressedSize;
    }

    /**
     * @param int $compressedSize
     * @return Zip64ExtendedInformation
     */
    public function setCompressedSize(int $compressedSize): Zip64ExtendedInformation
    {
        $obj = clone $this;
        $obj->compressedSize = $compressedSize;
        return $obj;
    }

    /**
     * @return int|null
     */
    public function getRelativeHeaderOffset()
    {
        return $this->relativeHeaderOffset;
    }

    /**
     * @param int $relativeHeaderOffset
     * @return Zip64ExtendedInformation
     */
    public function setRelativeHeaderOffset(int $relativeHeaderOffset): Zip64ExtendedInformation
    {
        $obj = clone $this;
        $obj->relativeHeaderOffset = $relativeHeaderOffset;
        return $obj;
    }

    /**
     * @return int|null
     */
    public function getDiskStartNumber()
    {
        return $this->diskStartNumber;
    }

    /**
     * @param int $diskStartNumber
     * @return Zip64ExtendedInformation
     */
    public function setDiskStartNumber(int $diskStartNumber): Zip64ExtendedInformation
    {
        $obj = clone $this;
        $obj->diskStartNumber = $diskStartNumber;
        return $obj;
    }
}
