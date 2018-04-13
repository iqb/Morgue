<?php

/*
 * (c) 2018 Dennis Birkholz <dennis@birkholz.org>
 *
 * $Id$
 * Author:    $Format:%an <%ae>, %ai$
 * Committer: $Format:%cn <%ce>, %ci$
 */

namespace iqb\stream;

if (!\in_array(SubStream::SCHEME, \stream_get_wrappers())) {
    \stream_wrapper_register(SubStream::SCHEME, SubStream::class);
}
