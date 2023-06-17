<?php

namespace Nevadskiy\Downloader\Exceptions;

use Exception;

/**
 * @todo use separate method from constructor for setting path.
 */
class DirectoryMissingException extends Exception
{
    /**
     * A path of the missing directory.
     *
     * @var string
     */
    protected $path;

    /**
     * Make a new exception instance.
     */
    public function __construct(string $path)
    {
        $this->path = $path;

        parent::__construct(sprintf('Directory "%s" does not exist', $path));
    }

    /**
     * Get a path of the missing directory.
     */
    public function getPath(): string
    {
        return $this->path;
    }
}
