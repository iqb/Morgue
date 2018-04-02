<?php

namespace iqb\zip;

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
