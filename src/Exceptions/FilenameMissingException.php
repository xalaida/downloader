<?php

namespace Nevadskiy\Downloader\Exceptions;

class FilenameMissingException extends DownloaderException
{
    /**
     * Make a new exception instance.
     */
    public static function new(): self
    {
        return new static('Expected a file path, but a directory path was given.');
    }
}
