<?php

namespace Nevadskiy\Downloader\Exceptions;

class FileExistsException extends DownloaderException
{
    /**
     * A path of the file.
     *
     * @var string
     */
    protected $path;

    /**
     * Make a new exception instance from the given path.
     */
    public static function from(string $path): self
    {
        $e = new static(sprintf('File [%s] already exists.', $path));
        $e->path = $path;

        return $e;
    }

    /**
     * Get a path of the file.
     */
    public function getPath(): string
    {
        return $this->path;
    }
}
