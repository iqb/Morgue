<?php

namespace iqb\stream;

/**
 * A SubStream wraps another stream and provides access to a portion of that stream.
 * A SubStream is read only.
 * The wrapped stream/resource must be seekable for SubStream to work.
 *
 * The URL schema is: fopen("iqb.substream://$startindex:$length/$resourceId")
 */
final class SubStream
{
    const SCHEME = 'iqb.substream';

    /**
     * @var resource
     */
    public $context;

    /**
     * @var resource
     */
    private $handle;

    /**
     * @var int
     */
    private $enforceOffsetMin;

    /**
     * @var int
     */
    private $enforceOffsetMax;

    /**
     * Current offset
     * @var int
     */
    private $offset = 0;


    public function stream_close()
    {
    }


    public function stream_eof()
    {
        echo "feof {$this->offset} : {$this->enforceOffsetMax}\n";

        return ($this->offset >= $this->enforceOffsetMax);
    }


    public function stream_open(string $path, string $mode, int $options)
    {
        $errors = ($options & \STREAM_REPORT_ERRORS);
        $parts = \parse_url($path);

        if (!isset($parts['scheme']) || $parts['scheme'] !== self::SCHEME) {
            $errors && \trigger_error("Invalid URL scheme.", \E_USER_ERROR);
            return false;
        }

        if (!isset($parts['host']) || !\is_numeric($parts['host'])) {
            $errors && \trigger_error("Invalid start offset.", \E_USER_ERROR);
            return false;
        }

        if (!isset($parts['port']) || !\is_numeric($parts['port'])) {
            $errors && \trigger_error("Invalid length.", \E_USER_ERROR);
            return false;
        }

        if (!isset($parts['path']) || !\is_numeric(\substr($parts['path'], 1))) {
            $errors && \trigger_error("Invalid resource to wrap.", \E_USER_ERROR);
            return false;
        }

        $this->enforceOffsetMin = \intval($parts['host']);
        $this->enforceOffsetMax = $this->enforceOffsetMin + \intval($parts['port']);
        $resource = \intval(\substr($parts['path'], 1));
        $resources = \get_resources();

        if (!isset($resources[$resource])) {
            $errors && \trigger_error("Resource not available.", \E_USER_ERROR);
            return false;
        }

        $this->handle = $resources[$resource];
        return true;
    }


    public function stream_read(int $count)
    {
        $realCount = \min($count, $this->enforceOffsetMax - $this->offset);

        if ($realCount > 0) {
            \fseek($this->handle, $this->offset);
            if (($data = \fread($this->handle, $realCount)) !== false) {
                $this->offset += \strlen($data);
            }

            return $data;
        } else {
            return false;
        }
    }


    public function stream_seek(int $offset, int $whence = \SEEK_SET)
    {
        if ($whence === \SEEK_SET) {
            $newOffset = $this->enforceOffsetMin + $offset;
        } elseif ($whence === \SEEK_CUR) {
            $newOffset = $this->offset + $offset;
        } elseif ($whence === \SEEK_END) {
            $newOffset = $this->enforceOffsetMax + $offset;
        } else {
            return false;
        }

        if (($newOffset < $this->enforceOffsetMin) || ($this->enforceOffsetMax <= $newOffset)) {
            return false;
        }

        $this->offset = $newOffset;
        return true;
    }


    public function stream_tell()
    {
        if ($this->offset < $this->enforceOffsetMin || $this->enforceOffsetMax <= $this->offset) {
            return;
        }

        return $this->offset - $this->enforceOffsetMin;
    }


    public function stream_stat()
    {
        return [
            7 => ($this->enforceOffsetMax - $this->enforceOffsetMin),
            'size' => ($this->enforceOffsetMax - $this->enforceOffsetMin),
        ];
    }
}
