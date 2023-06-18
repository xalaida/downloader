<?php

namespace Nevadskiy\Downloader\Exceptions;

/**
 * @todo rename to make it clear when it can be thrown (when impossible to include timestamps according to destination path)
 */
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
