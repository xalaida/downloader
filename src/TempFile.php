<?php

namespace Nevadskiy\Downloader;

use Exception;
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

        $this->ensureDirectoryWritable($directory);

        $this->path = tempnam($directory, 'tmp_');
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
     * Determine if the temp file instance is destroyed.
     */
    public function destroyed(): bool
    {
        if (! $this->path) {
            return true;
        }

        return false;
    }

    /**
     * Write temp file using the given callback.
     */
    public function writeUsing(callable $callback)
    {
        $this->ensureNotDestroyed();

        $stream = fopen($this->path, 'wb+');

        try {
            $result = $callback($stream);

            fclose($stream);

            return $result;
        } catch (Exception $e) {
            fclose($stream);

            throw $e;
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
        $this->ensureNotDestroyed();

        // TODO: assert path is not a directory
        // TODO: assert path directory is writable

        if (false === @rename($this->path, $path)) {
            throw new RuntimeException(sprintf('Could not rename a "%s" file', $this->path));
        }

        $this->path = null;
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
        $this->ensureNotDestroyed();

        if (false === @unlink($this->path)) {
            throw new RuntimeException(sprintf('Could not delete a "%s" file', $this->path));
        }

        $this->path = null;
    }

    /**
     * Destroy the temp file instance.
     */
    public function __destruct()
    {
        if (! $this->destroyed()) {
            $this->delete();
        }
    }

    /**
     * Ensure that the temp file instance is not destroyed.
     */
    protected function ensureNotDestroyed()
    {
        if ($this->destroyed()) {
            throw new RuntimeException('The TempFile instance is destroyed');
        }
    }

    /**
     * Ensure the given directory exists and is writable.
     */
    protected function ensureDirectoryWritable(string $directory)
    {
        if (! is_dir($directory) || ! is_writable($directory)) {
            throw new RuntimeException(sprintf('The "%s" must be a writable directory', $directory));
        }
    }
}
