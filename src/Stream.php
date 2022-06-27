<?php

namespace Nevadskiy\Downloader;

use Exception;

class Stream
{
    /**
     * The file resource.
     *
     * @var false|resource
     */
    protected $resource;

    /**
     * Make a new stream instance.
     */
    public function __construct(string $path, string $mode = 'wb+')
    {
        $this->resource = fopen($path, $mode);

        if ($this->resource === false) {
            throw new Exception(sprintf('Could not open %s', $path));
        }
    }

    /**
     * Get the stream resource.
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Destroy the stream instance.
     */
    public function __destruct()
    {
        fclose($this->resource);
    }
}
