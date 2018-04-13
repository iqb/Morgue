<?php

/*
 * (c) 2018 Dennis Birkholz <dennis@birkholz.org>
 *
 * $Id$
 * Author:    $Format:%an <%ae>, %ai$
 * Committer: $Format:%cn <%ce>, %ci$
 */

namespace iqb\stream;

const SUBSTREAM_SCHEME = 'iqb.substream';

if (!\in_array(SUBSTREAM_SCHEME, \stream_get_wrappers())) {
    \stream_wrapper_register(SUBSTREAM_SCHEME, SubStream::class);
}
