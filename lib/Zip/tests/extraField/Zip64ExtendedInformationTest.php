<?php

namespace morgue\zip\extraField;

use morgue\zip\CentralDirectoryHeader;
use morgue\zip\ExtraField;
use const morgue\zip\MAX_INT_16;
use const morgue\zip\MAX_INT_32;
use PHPUnit\Framework\TestCase;

final class Zip64ExtendedInformationTest extends TestCase
{
    public function dataProvider()
    {
        // originalSize, compressedSize, relativeHeaderOffset, diskNumber
        $sizesLong = [
            0,
            12345,
            MAX_INT_16,
            MAX_INT_32,
            64*1024*1024*1024,
            \PHP_INT_MAX
        ];

        $sizesShort = [
            0,
            12345,
            MAX_INT_16,
            2*1024*1024*1024,
            0x7FFFFFFF,
        ];

        $data = [];
        foreach ($sizesLong as $originalSize) {
            foreach ($sizesLong as $compressedSize) {
                foreach ($sizesLong as $relativeHeaderOffset) {
                    foreach ($sizesShort as $diskNumber) {
                        $name = $originalSize . ', ' . $compressedSize . ', ' . $relativeHeaderOffset . ', ' . $diskNumber;
                        $dataSize = 0;
                        $encoded = '';

                        if ($originalSize >= MAX_INT_32) {
                            $dataSize += 8;
                            $encoded .= \pack('P', $originalSize);
                            $parameterOriginalSize = $originalSize;
                            $legacyOriginalSize = MAX_INT_32;
                        } else {
                            $legacyOriginalSize = $originalSize;
                            $parameterOriginalSize = null;
                        }

                        if ($compressedSize >= MAX_INT_32) {
                            $dataSize += 8;
                            $encoded .= \pack('P', $compressedSize);
                            $legacyCompressedSize = MAX_INT_32;
                            $parameterCompressedSize = $compressedSize;
                        } else {
                            $legacyCompressedSize = $compressedSize;
                            $parameterCompressedSize = null;
                        }

                        if ($relativeHeaderOffset >= MAX_INT_32) {
                            $dataSize += 8;
                            $encoded .= \pack('P', $relativeHeaderOffset);
                            $legacyRelativeHeaderOffset = MAX_INT_32;
                            $parameterRelativeHeaderOffset = $relativeHeaderOffset;
                        } else {
                            $legacyRelativeHeaderOffset = $relativeHeaderOffset;
                            $parameterRelativeHeaderOffset = null;
                        }

                        if ($diskNumber >= MAX_INT_16) {
                            $dataSize += 4;
                            $encoded .= \pack('V', $diskNumber);
                            $legacyDiskNumber = MAX_INT_16;
                            $parameterDiskNumber = $diskNumber;
                        } else {
                            $legacyDiskNumber = $diskNumber;
                            $parameterDiskNumber = null;
                        }

                        $encoded = \pack('vv', Zip64ExtendedInformation::ID, $dataSize) . $encoded;

                        $data[$name] = [
                            $parameterOriginalSize,
                            $parameterCompressedSize,
                            $parameterRelativeHeaderOffset,
                            $parameterDiskNumber,
                            new CentralDirectoryHeader(
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                $legacyCompressedSize,
                                $legacyOriginalSize,
                                $legacyDiskNumber,
                                0,
                                0,
                                $legacyRelativeHeaderOffset
                            ),
                            $encoded
                        ];
                    }
                }
            }
        }
        return $data;
    }

    /**
     * @dataProvider dataProvider
     */
    public function testExtraFieldParsing(int $originalSize = null, int $compressedSize = null, int $relativeHeaderOffset = null, int $diskNumber = null, CentralDirectoryHeader $context, string $expectedOutput)
    {
        $extendedField = Zip64ExtendedInformation::parse($expectedOutput, $context);
        $this->assertSame($originalSize, $extendedField->getOriginalSize(), 'Parsed original size');
        $this->assertSame($compressedSize, $extendedField->getCompressedSize(), 'Parsed compressed size');
        $this->assertSame($relativeHeaderOffset, $extendedField->getRelativeHeaderOffset(), 'Parsed relative header offset');
        $this->assertSame($diskNumber, $extendedField->getDiskStartNumber(), 'Parsed disk start number');
    }

    /**
     * @dataProvider dataProvider
     */
    public function testExtraFieldGeneration(int $originalSize = null, int $compressedSize = null, int $relativeHeaderOffset = null, int $diskNumber = null, CentralDirectoryHeader $context, string $expectedOutput)
    {
        $extendedField = new Zip64ExtendedInformation($originalSize, $compressedSize, $relativeHeaderOffset, $diskNumber);
        $this->assertSame($expectedOutput, ExtraField::marshal($extendedField), 'Generated field data');
    }

    public function testImmutability()
    {
        $old = new Zip64ExtendedInformation();

        // Compressed size
        $new = $old->setCompressedSize(12345);
        $this->assertNotSame($old, $new);
        $this->assertNull($old->getCompressedSize());
        $this->assertSame(12345, $new->getCompressedSize());

        $old = $new;
        $new = $old->setOriginalSize(23456);
        $this->assertNotSame($old, $new);
        $this->assertNull($old->getOriginalSize());
        $this->assertSame(23456, $new->getOriginalSize());

        $old = $new;
        $new = $old->setRelativeHeaderOffset(34567);
        $this->assertNotSame($old, $new);
        $this->assertNull($old->getRelativeHeaderOffset());
        $this->assertSame(34567, $new->getRelativeHeaderOffset());

        $old = $new;
        $new = $old->setDiskStartNumber(45678);
        $this->assertNotSame($old, $new);
        $this->assertNull($old->getDiskStartNumber());
        $this->assertSame(45678, $new->getDiskStartNumber());
    }

    public function testInvalidLength()
    {
        $input = \pack('vv', Zip64ExtendedInformation::ID, 0);
        $context = new CentralDirectoryHeader(0,0,0,0,0,0,0,MAX_INT_32,0,0,0,0,0);

        $this->expectException(\InvalidArgumentException::class);
        Zip64ExtendedInformation::parse($input, $context);
    }

    public function testInvalidHeaderId()
    {
        $input = \pack('vv', 12345, 0);
        $context = new CentralDirectoryHeader(0,0,0,0,0,0,0,0,0,0,0,0,0);

        $this->expectException(\InvalidArgumentException::class);
        Zip64ExtendedInformation::parse($input, $context);
    }
}
