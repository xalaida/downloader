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
        $this->path = tempnam($directory ?: sys_get_temp_dir(), 'tmp_');
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
     * Fill temp file using the given callback.
     */
    public function fillUsing(callable $callback)
    {
        $this->ensureFileExists();

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
     * Move a file to the given path.
     */
    public function move(string $path)
    {
        $this->ensureFileExists();

        @unlink($path);

        rename($this->path, $path);

        $this->path = $path;
    }

    /**
     * Delete a file.
     */
    public function delete()
    {
        $this->ensureFileExists();

        unlink($this->path);
    }

    /**
     * Ensure that the file exists.
     */
    protected function ensureFileExists()
    {
        if (! $this->path) {
            throw new RuntimeException('File does not exists.');
        }
    }
}
