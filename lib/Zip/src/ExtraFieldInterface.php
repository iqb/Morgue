<?php

namespace morgue\zip;

interface ExtraFieldInterface
{
    /**
     * Parse the extra field from the supplied raw input
     *
     * @param string $input
     * @param mixed $context The context this extra field comes from, e.g. a CentralDirectoryHeader
     * @return static
     */
    public static function parse(string $input, $context = null);

    /**
     * The header identifier for the extra field
     * @return int
     */
    public function getHeaderId();

    /**
     * Get the size in bytes of the data of the extra field
     * @return int
     */
    public function getDataSize();

    /**
     * Get the binary data representation of the extra field
     * @return string
     */
    public function getData();
}
