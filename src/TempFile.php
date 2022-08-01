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
    public function __construct(string $directory = null, bool $handleShutdown = true)
    {
        $directory = $directory ?: sys_get_temp_dir();

        $this->ensureDirectoryIsWritable($directory);

        $this->path = tempnam($directory, 'tmp_');

        if ($handleShutdown) {
            $this->reginsterShutdownHandler();
        }
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

        if (false === @rename($this->path, $path)) {
            throw new RuntimeException(sprintf('Could not rename a "%s" file', $this->path));
        }

        $this->markAsDestroyed();
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

        $this->markAsDestroyed();
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
        if (! $this->destroyed()) {
            $this->delete();
        }
    }

    /**
     * Register the shutdown handler.
     */
    protected function reginsterShutdownHandler()
    {
        register_shutdown_function(static function(string $path) {
            @unlink($path);
        }, $this->path);
    }

    /**
     * Mark the temp file instance as destroyed.
     */
    protected function markAsDestroyed()
    {
        $this->path = null;
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
     * Determine if the temp file instance is destroyed.
     */
    protected function destroyed(): bool
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
