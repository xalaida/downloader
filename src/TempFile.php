<?php

namespace Nevadskiy\Downloader;

use Exception;

class TempFile
{
    /**
     * @var string
     */
    private $path;

    public function __construct(string $directory = null)
    {
        $this->path = tempnam($directory ?: sys_get_temp_dir(), 'tmp_');
    }

    /**
     * Create a temp file using the given callback in the given directory.
     */
    public static function createUsing(callable $callback, string $directory = null): TempFile
    {
        $file = new static($directory);

        $file->fillUsing($callback);

        return $file;
    }

    /**
     * Fill temp file using the given callback.
     */
    public function fillUsing(callable $callback)
    {
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

    public function saveAs(string $path)
    {
        @unlink($path);

        rename($this->path, $path);

        $this->path = $path;
    }

    public function delete()
    {
        unlink($this->path);
    }

    public function getPath()
    {
        return $this->path;
    }
}
