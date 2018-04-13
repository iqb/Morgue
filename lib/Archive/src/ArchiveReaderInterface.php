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
 * @author Dennis Birkholz <dennis@birkholz.org>
 */
interface ArchiveReaderInterface
{
    /**
     * Read an archive from a stream
     *
     * @param resource $stream A seekable stream
     * @param int $offset Stream position to seek to before reading the archive
     * @return Archive
     */
    function read($stream, int $offset = 0) : Archive;
}
