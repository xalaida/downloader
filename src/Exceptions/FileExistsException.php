<?php

namespace Nevadskiy\Downloader\Exceptions;

use Exception;

/**
 * @todo use separate method from constructor for setting path.
 */
class FileExistsException extends Exception
{
    /**
     * A path of the file.
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

        parent::__construct(sprintf('File "%s" already exists', $this->getPath()));
    }

    /**
     * Get a path of the file.
     */
    public function getPath(): string
    {
        return $this->path;
    }
}
