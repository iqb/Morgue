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
interface ArchiveWriterInterface
{
    /**
     * Write an archive to a stream.
     * Returns the updated Archive containing information only known after the Archive is written,
     *  e.g. compressed size of entries.
     *
     * @param Archive $archive The Archive describing the archive structure
     * @param resource $targetStream A seekable stream
     * @return Archive The updated Archive structure
     */
    function write(Archive $archive, $targetStream) : Archive;
}
