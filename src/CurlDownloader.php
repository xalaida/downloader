<?php

namespace Nevadskiy\Downloader;

/**
 * @TODO: add possibility to specify additional cURL options by curl_setopt(). https://www.php.net/manual/en/function.curl-setopt.php
 */
class CurlDownloader implements Downloader
{
    /**
     * The cURL handle callbacks.
     *
     * @var array
     */
    protected $curlHandleCallbacks = [];

    /**
     * Add a cURL handle callback.
     *
     * @return void
     */
    public function withCurlHandle(callable $callback)
    {
        $this->curlHandleCallbacks[] = $callback;
    }

    /**
     * @inheritdoc
     */
    public function download(string $url, string $directory, string $name = null)
    {
        // TODO: validate URL.

        $path = $this->getFilePath($directory, $name ?: $this->guessFileName($url));

        $stream = new Stream($path);

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_FILE, $stream->getResource());

        foreach ($this->curlHandleCallbacks as $callback) {
            $callback($ch);
        }

        curl_exec($ch);

        curl_close($ch);
    }

    /**
     * Guess the file name by the given URL.
     */
    protected function guessFileName(string $url): string
    {
        $position = strrpos($url, '/');

        if ($position === false) {
            // TODO: provide default file name.
        }

        return substr($url, $position + 1);
    }

    /**
     * Get the file path by the given file name and directory.
     */
    protected function getFilePath(string $directory, string $name): string
    {
        return rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . trim($name, DIRECTORY_SEPARATOR);
    }
}
