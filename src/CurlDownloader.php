<?php

namespace Nevadskiy\Downloader;

use InvalidArgumentException;
use RuntimeException;

class CurlDownloader implements Downloader
{
    /**
     * The cURL options array.
     *
     * @var array
     */
    protected $curlOptions;

    /**
     * The cURL handle callbacks.
     *
     * @var array
     */
    protected $curlHandleCallbacks = [];

    /**
     * Indicates if the downloader should overwrite the content if a file already exists.
     *
     * @var bool
     */
    protected $overwrite = false;

    /**
     * Make a new downloader instance.
     */
    public function __construct(array $curlOptions = [])
    {
        $this->curlOptions = $this->curlOptions() + $curlOptions;
    }

    /**
     * The default cURL options.
     */
    protected function curlOptions(): array
    {
        return [
            CURLOPT_FAILONERROR => true,
        ];
    }

    /**
     * Add a cURL option with the given value.
     */
    public function withCurlOption($option, $value)
    {
        $this->curlOptions[$option] = $value;
    }

    /**
     * Add a cURL handle callback.
     */
    public function withCurlHandle(callable $callback)
    {
        $this->curlHandleCallbacks[] = $callback;
    }

    /**
     * Overwrite the content if a file already exists.
     */
    public function overwrite(bool $overwrite = true)
    {
        $this->overwrite = $overwrite;
    }

    /**
     * @inheritdoc
     */
    public function download(string $url, string $path)
    {
        $this->ensureUrlIsValid($url);

        $this->ensureFileCanBeWritten($path);

        $error = $this->withFileStream($path, function ($stream) use ($url) {
            return $this->writeStreamUsingCurl($url, $stream);
        });

        if ($error) {
            $this->handleError($error, $path);
        }
    }

    /**
     * Ensure that the given URL is valid.
     */
    protected function ensureUrlIsValid(string $url)
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('The given URL is invalid.');
        }
    }

    /**
     * Ensure that the file can be written by the given path.
     */
    protected function ensureFileCanBeWritten(string $path)
    {
        if (! $this->overwrite && file_exists($path)) {
            throw new RuntimeException('A file "%" already exists.');
        }
    }

    /**
     * Apply a callback to a file stream.
     */
    private function withFileStream(string $path, callable $callback)
    {
        $stream = $this->openFileStream($path);

        $result = $callback($stream);

        fclose($stream);

        return $result;
    }

    /**
     * Open the file stream.
     *
     * @return resource
     */
    protected function openFileStream(string $path)
    {
        $stream = @fopen($path, 'wb+');

        if (! $stream) {
            throw new RuntimeException(sprintf('Cannot open file %s', $path));
        }

        return $stream;
    }

    /**
     * Write a stream using cURL.
     *
     * @param resource $stream
     * @return string|null
     */
    private function writeStreamUsingCurl(string $url, $stream)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_FILE, $stream);

        foreach ($this->curlOptions as $option => $value) {
            curl_setopt($ch, $option, $value);
        }

        foreach ($this->curlHandleCallbacks as $handleCallbacks) {
            $handleCallbacks($ch);
        }

        $response = curl_exec($ch);

        $error = $this->captureError($ch, $response);

        curl_close($ch);

        return $error;
    }

    /**
     * Capture error from the cURL response.
     *
     * @param resource $ch
     * @return string|null
     */
    protected function captureError($ch, bool $response)
    {
        if ($response) {
            return null;
        }

        return curl_error($ch);
    }

    /**
     * Handle the error during downloading.
     */
    protected function handleError(string $error, string $path)
    {
        unlink($path);

        throw new RuntimeException($error);
    }
}
