<?php

namespace Nevadskiy\Downloader\Exceptions;

class DestinationFileMissingException extends DownloaderException
{
    /**
     * Make a new exception instance.
     */
    public static function new(): self
    {
        return new static('Destination file is missing.');
    }
}
