<?php

namespace Nevadskiy\Downloader\Exceptions;

use Exception;

class DirectoryMissingException extends Exception
{
    /**
     * A path of the missing directory.
     *
     * @var string
     */
    protected $path;

    /**
     * Make a new exception instance from the given path.
     */
    public static function from(string $path): self
    {
        $e = new static(sprintf('Directory [%s] does not exits.', $path));
        $e->path = $path;

        return $e;
    }

    /**
     * Get a path of the missing directory.
     */
    public function getPath(): string
    {
        return $this->path;
    }
}
