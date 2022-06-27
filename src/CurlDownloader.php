<?php

namespace Nevadskiy\Downloader;

use Exception;

class CurlDownloader implements Downloader
{
    /**
     * The cURL handle callbacks.
     *
     * @var array
     */
    protected $curlHandleCallbacks = [];

    /**
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
        $name = $name ?: $this->guessFileName($url);

        $path = $this->getFilePath($directory, $name);

        $stream = fopen($path, 'wb+');

        if ($stream === false) {
            throw new Exception('Could not open: ' . $path);
        }

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_FILE, $stream);

        foreach ($this->curlHandleCallbacks as $callback) {
            $callback($ch);
        }

        // Timeout if the file doesn't download after 20 seconds.
        // curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        curl_exec($ch);

        // If there was an error, throw an Exception
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }

        // Get the HTTP status code.
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Close the cURL handler.
        curl_close($ch);

        // Close the file handler.
        fclose($stream);

        if ($statusCode === 200) {
            echo 'Downloaded!';
        } else {
            echo "Status Code: " . $statusCode;
        }
    }

    protected function guessFileName(string $url): string
    {
        $position = strrpos($url, '/');

        if ($position === false) {
            // cannot define file name...
        }

        return substr($url, $position + 1);
    }

    /**
     * @param string $directory
     * @param string $name
     * @return string
     */
    protected function getFilePath(string $directory, string $name): string
    {
        return rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . trim($name, DIRECTORY_SEPARATOR);
    }
}
