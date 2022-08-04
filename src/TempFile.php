<?php

namespace Nevadskiy\Downloader;

use RuntimeException;

class TempFile
{
    /**
     * A path of the file.
     *
     * @var string|null
     */
    protected $path;

    /**
     * Make a new temp file instance.
     */
    public function __construct(string $directory = null)
    {
        $directory = $directory ?: sys_get_temp_dir();

        $this->ensureDirectoryIsWritable($directory);

        $this->path = tempnam($directory, 'tmp_');
    }

    /**
     * Write temp file using the given callback.
     */
    public function writeUsing(callable $callback)
    {
        $this->ensureNotDeleted();

        $stream = fopen($this->path, 'wb+');

        try {
            return $callback($stream);
        } finally {
            fclose($stream);
        }
    }

    /**
     * Write a content to the file.
     */
    public function write(string $content)
    {
        $this->writeUsing(function ($stream) use ($content) {
            fwrite($stream, $content);
        });
    }

    /**
     * Persist a file to the given path
     */
    public function persist(string $path)
    {
        $this->ensureNotDeleted();

        if (false === @rename($this->path, $path)) {
            throw new RuntimeException(sprintf('Could not rename a "%s" file to "%s"', $this->path, $path));
        }

        $this->markAsDeleted();
    }

    /**
     * Alias to persist a file to the given path.
     */
    public function save(string $path)
    {
        $this->persist($path);
    }

    /**
     * Delete a file.
     */
    public function delete()
    {
        $this->ensureNotDeleted();

        if (false === @unlink($this->path)) {
            throw new RuntimeException(sprintf('Could not delete a "%s" file', $this->path));
        }

        $this->markAsDeleted();
    }

    /**
     * Get a path of the file.
     *
     * @return string|null
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Destroy the temp file instance.
     */
    public function __destruct()
    {
        if (! $this->deleted()) {
            $this->delete();
        }
    }

    /**
     * Mark the temp file instance as deleted.
     */
    protected function markAsDeleted()
    {
        $this->path = null;
    }

    /**
     * Ensure that the temp file instance is not deleted.
     */
    protected function ensureNotDeleted()
    {
        if ($this->deleted()) {
            throw new RuntimeException('The TempFile instance is deleted');
        }
    }

    /**
     * Determine if the temp file instance is deleted.
     */
    protected function deleted(): bool
    {
        if (! $this->path) {
            return true;
        }

        return false;
    }

    /**
     * Ensure the given directory exists and is writable.
     */
    protected function ensureDirectoryIsWritable(string $directory)
    {
        if (! is_dir($directory) || ! is_writable($directory)) {
            throw new RuntimeException(sprintf('The "%s" must be a writable directory', $directory));
        }
    }
}
