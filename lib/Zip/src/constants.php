<?php

namespace morgue\zip;

use const morgue\archive\COMPRESSION_METHOD_BZIP2;
use const morgue\archive\COMPRESSION_METHOD_DEFLATE;
use const morgue\archive\COMPRESSION_METHOD_DEFLATE64;
use const morgue\archive\COMPRESSION_METHOD_LZMA;
use const morgue\archive\COMPRESSION_METHOD_STORE;
use iqb\ZipArchive;

const MAX_INT_16 = 0xFFFF;
const MAX_INT_32 = 0xFFFFFFFF;

// from zip file specification:
//    4.4.2.1 The upper byte indicates the compatibility of the file
//        attribute information.  If the external file attributes
//        are compatible with MS-DOS and can be read by PKZIP for
//        DOS version 2.04g then this value will be zero.  If these
//        attributes are not compatible, then this value will
//        identify the host system on which the attributes are
//        compatible.  Software can use this information to determine
//        the line record format for text files etc.
//
//    4.4.2.2 The current mappings are:
//         0 - MS-DOS and OS/2 (FAT / VFAT / FAT32 file systems)
//         1 - Amiga                     2 - OpenVMS
//         3 - UNIX                      4 - VM/CMS
//         5 - Atari ST                  6 - OS/2 H.P.F.S.
//         7 - Macintosh                 8 - Z-System
//         9 - CP/M                     10 - Windows NTFS
//        11 - MVS (OS/390 - Z/OS)      12 - VSE
//        13 - Acorn Risc               14 - VFAT
//        15 - alternate MVS            16 - BeOS
//        17 - Tandem                   18 - OS/400
//        19 - OS X (Darwin)            20 thru 255 - unused

const HOST_COMPATIBILITY_MSDOS = 0;
const HOST_COMPATIBILITY_AMIGA = 1;
const HOST_COMPATIBILITY_OPENVMS = 2;
const HOST_COMPATIBILITY_UNIX = 3;
const HOST_COMPATIBILITY_VM_CMS = 4;
const HOST_COMPATIBILITY_ATARI_ST = 5;
const HOST_COMPATIBILITY_OS2 = 6;
const HOST_COMPATIBILITY_MACINTOSH = 7;
const HOST_COMPATIBILITY_Z_SYSTEM = 8;
const HOST_COMPATIBILITY_CP_M = 9;
const HOST_COMPATIBILITY_WINDOWS_NTFS = 10;
const HOST_COMPATIBILITY_MVS = 11;
const HOST_COMPATIBILITY_VSE = 12;
const HOST_COMPATIBILITY_ACORN_RISC = 13;
const HOST_COMPATIBILITY_VFAT = 14;
const HOST_COMPATIBILITY_ALTERNATE_MVS = 15;
const HOST_COMPATIBILITY_BEOS = 16;
const HOST_COMPATIBILITY_TANDEM = 17;
const HOST_COMPATIBILITY_OS400 = 18;
const HOST_COMPATIBILITY_OS_X_DARWIN = 19;

const HOST_COMPATIBILITY_MAPPING = [
    HOST_COMPATIBILITY_MSDOS => 'MS-DOS',
    HOST_COMPATIBILITY_AMIGA => 'Amiga',
    HOST_COMPATIBILITY_OPENVMS => 'OpenVMS',
    HOST_COMPATIBILITY_UNIX => 'Unix',
    HOST_COMPATIBILITY_VM_CMS => 'VM/CMS',
    HOST_COMPATIBILITY_ATARI_ST => 'Atari ST',
    HOST_COMPATIBILITY_OS2 => 'OS/2',
    HOST_COMPATIBILITY_MACINTOSH => 'Macintosh',
    HOST_COMPATIBILITY_Z_SYSTEM => 'Z-System',
    HOST_COMPATIBILITY_CP_M => 'CP/M',
    HOST_COMPATIBILITY_WINDOWS_NTFS => 'Windows NTFS',
    HOST_COMPATIBILITY_MVS => 'MVS',
    HOST_COMPATIBILITY_VSE => 'VSE',
    HOST_COMPATIBILITY_ACORN_RISC => 'Acorn Risc',
    HOST_COMPATIBILITY_VFAT => 'VFAT',
    HOST_COMPATIBILITY_ALTERNATE_MVS => 'alternate VMS',
    HOST_COMPATIBILITY_BEOS => 'BeOS',
    HOST_COMPATIBILITY_TANDEM => 'Tandem',
    HOST_COMPATIBILITY_OS400 => 'OS/400',
    HOST_COMPATIBILITY_OS_X_DARWIN => 'OS X (Darwin)',
];

// DOS attributes
const DOS_ATTRIBUTE_READONLY  =  1;
const DOS_ATTRIBUTE_HIDDEN    =  2;
const DOS_ATTRIBUTE_SYSTEM    =  4;
const DOS_ATTRIBUTE_VOLUME    =  8;
const DOS_ATTRIBUTE_DIRECTORY = 16;
const DOS_ATTRIBUTE_ARCHIVE   = 32;


const DOS_ATTRIBUTE_MAPPING = [
    DOS_ATTRIBUTE_READONLY => 'Readonly',
    DOS_ATTRIBUTE_HIDDEN => 'Hidden',
    DOS_ATTRIBUTE_SYSTEM => 'System',
    DOS_ATTRIBUTE_VOLUME => 'Volume Label',
    DOS_ATTRIBUTE_DIRECTORY => 'Directory',
    DOS_ATTRIBUTE_ARCHIVE => 'Archive',
];

/**
 * stored (uncompressed)
 */
const ZIP_COMPRESSION_METHOD_STORE = 0;

/**
 * shrunk
 */
const ZIP_COMPRESSION_METHOD_SHRINK = 1;

/**
 * reduced with factor 1
 */
const ZIP_COMPRESSION_METHOD_REDUCE_1 = 2;

/**
 * reduced with factor 2
 */
const ZIP_COMPRESSION_METHOD_REDUCE_2 = 3;

/**
 * reduced with factor 3
 */
const ZIP_COMPRESSION_METHOD_REDUCE_3 = 4;

/**
 * reduced with factor 4
 */
const ZIP_COMPRESSION_METHOD_REDUCE_4 = 5;

/**
 * imploded
 */
const ZIP_COMPRESSION_METHOD_IMPLODE = 6;

/**
 * Tokenizing compression algorithm (reserved only, most probably nowhere implemented)
 */
const ZIP_COMPRESSION_METHOD_TOKENIZE = 7;

/**
 * deflated
 */
const ZIP_COMPRESSION_METHOD_DEFLATE = 8;

/**
 * deflate64
 */
const ZIP_COMPRESSION_METHOD_DEFLATE64 = 9;

/**
 * PKWARE Data Compression Library Imploding (old IBM TERSE)
 */
const ZIP_COMPRESSION_METHOD_PKWARE_IMPLODE = 10;

/**
 * BZip2 algorithm
 */
const ZIP_COMPRESSION_METHOD_BZIP2 = 12;

/**
 * LZMA (EFS)
 */
const ZIP_COMPRESSION_METHOD_LZMA = 14;

/**
 * File is compressed using IBM TERSE
 */
const ZIP_COMPRESSION_METHOD_TERSE = 18;

/**
 * IBM LZ77 z Architecture (PFS)
 */
const ZIP_COMPRESSION_METHOD_LZ77 = 19;

/**
 * WavPack compressed data
 * @link http://www.wavpack.com
 */
const ZIP_COMPRESSION_METHOD_WAVPACK = 97;

/**
 * PPMd version I, Rev 1
 * @link http://www.compression.ru/ds/
 */
const ZIP_COMPRESSION_METHOD_PPMD = 98;


/**
 * Mapping of zip compression methods to generic compression methods
 */
const COMPRESSION_METHOD_MAPPING = [
    ZIP_COMPRESSION_METHOD_STORE     => COMPRESSION_METHOD_STORE,
    ZIP_COMPRESSION_METHOD_DEFLATE   => COMPRESSION_METHOD_DEFLATE,
    ZIP_COMPRESSION_METHOD_DEFLATE64 => COMPRESSION_METHOD_DEFLATE64,
    ZIP_COMPRESSION_METHOD_BZIP2     => COMPRESSION_METHOD_BZIP2,
    ZIP_COMPRESSION_METHOD_LZMA      => COMPRESSION_METHOD_LZMA,
];

const COMPRESSION_METHOD_REVERSE_MAPPING = [
    COMPRESSION_METHOD_STORE     => ZIP_COMPRESSION_METHOD_STORE,
    COMPRESSION_METHOD_DEFLATE   => ZIP_COMPRESSION_METHOD_DEFLATE,
    COMPRESSION_METHOD_DEFLATE64 => ZIP_COMPRESSION_METHOD_DEFLATE64,
    COMPRESSION_METHOD_BZIP2     => ZIP_COMPRESSION_METHOD_BZIP2,
    COMPRESSION_METHOD_LZMA      => ZIP_COMPRESSION_METHOD_LZMA,
];
