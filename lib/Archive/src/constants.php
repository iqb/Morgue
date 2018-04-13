<?php

/*
 * (c) 2018 Dennis Birkholz <dennis@birkholz.org>
 *
 * $Id$
 * Author:    $Format:%an <%ae>, %ai$
 * Committer: $Format:%cn <%ce>, %ci$
 */

namespace morgue\archive;

/**
 * Data is stored (not compressed)
 */
const COMPRESSION_METHOD_STORE = 'store';

/**
 * Data is compressed with deflate
 */
const COMPRESSION_METHOD_DEFLATE = 'deflate';

/**
 * Data is compressed with deflate64
 */
const COMPRESSION_METHOD_DEFLATE64 = 'deflate64';

/**
 * Data is compressed with bzip2
 */
const COMPRESSION_METHOD_BZIP2 = 'bzip2';

/**
 * Data is compressed with lzma
 */
const COMPRESSION_METHOD_LZMA = 'lzma';

// DOS attributes

const DOS_ATTRIBUTE_READONLY  =  1;
const DOS_ATTRIBUTE_HIDDEN    =  2;
const DOS_ATTRIBUTE_SYSTEM    =  4;
const DOS_ATTRIBUTE_VOLUME    =  8;
const DOS_ATTRIBUTE_DIRECTORY = 16;
const DOS_ATTRIBUTE_ARCHIVE   = 32;

const DOS_ATTRIBUTES_MAPPING = [
    DOS_ATTRIBUTE_READONLY => 'Readonly',
    DOS_ATTRIBUTE_HIDDEN => 'Hidden',
    DOS_ATTRIBUTE_SYSTEM => 'System',
    DOS_ATTRIBUTE_VOLUME => 'Volume Label',
    DOS_ATTRIBUTE_DIRECTORY => 'Directory',
    DOS_ATTRIBUTE_ARCHIVE => 'Archive',
];

const UNIX_ATTRIBUTE_USER_EXECUTE          = 00100;
const UNIX_ATTRIBUTE_USER_WRITE            = 00200;
const UNIX_ATTRIBUTE_USER_READ             = 00400;
const UNIX_ATTRIBUTE_GROUP_EXECUTE         = 00010;
const UNIX_ATTRIBUTE_GROUP_WRITE           = 00020;
const UNIX_ATTRIBUTE_GROUP_READ            = 00040;
const UNIX_ATTRIBUTE_OTHER_EXECUTE         = 00001;
const UNIX_ATTRIBUTE_OTHER_WRITE           = 00002;
const UNIX_ATTRIBUTE_OTHER_READ            = 00004;

const UNIX_ATTRIBUTE_SET_USER_ID           = 04000;
const UNIX_ATTRIBUTE_SET_GROUP_ID          = 02000;
const UNIX_ATTRIBUTE_STICKY                = 01000;

const UNIX_ATTRIBUTE_TYPE_SOCKET           = 0140000;
const UNIX_ATTRIBUTE_TYPE_SYMBOLIC_LINK    = 0120000;
const UNIX_ATTRIBUTE_TYPE_FILE             = 0100000;
const UNIX_ATTRIBUTE_TYPE_BLOCK_DEVICE     = 0060000;
const UNIX_ATTRIBUTE_TYPE_DIRECTORY        = 0040000;
const UNIX_ATTRIBUTE_TYPE_CHARACTER_DEVICE = 0020000;
const UNIX_ATTRIBUTE_TYPE_FIFO             = 0010000;

