<?php

/*
 * (c) 2018 Dennis Birkholz <dennis@birkholz.org>
 *
 * $Id$
 * Author:    $Format:%an <%ae>, %ai$
 * Committer: $Format:%cn <%ce>, %ci$
 */

namespace iqb;

/**
 * @author dennis
 */
class Environment
{
    private $compressFilters = [];
    private $decompressFilters = [];

    public function __construct()
    {
        $streamFilters = \array_flip(\stream_get_filters());

        if (isset($streamFilters['zlib.*'])) {
            $this->compressFilters[COMPRESSION_METHOD_DEFLATE]   = 'zlib.deflate';
            $this->decompressFilters[COMPRESSION_METHOD_DEFLATE] = 'zlib.inflate';
        }

        if (isset($streamFilters['bzip2.*'])) {
            $this->compressFilters[COMPRESSION_METHOD_BZIP2]   = 'bzip2.compress';
            $this->decompressFilters[COMPRESSION_METHOD_BZIP2] = 'bzip2.decompress';
        }
    }

    public function addCompressFilter(string $compressionMethod, string $filterName)
    {
        $this->compressFilters[$compressionMethod] = $filterName;
    }


    public function addDecompressFilter(string $compressionMethod, string $filterName)
    {
        $this->decompressFilters[$compressionMethod] = $filterName;
    }


    public function getCompressFilter(string $compressionMethod)
    {
        return (isset($this->compressFilters[$compressionMethod]) ? $this->compressFilters[$compressionMethod] : null);
    }


    public function getDecompressFilter(string $compressionMethod)
    {
        return (isset($this->decompressFilters[$compressionMethod]) ? $this->decompressFilters[$compressionMethod] : null);
    }
}
