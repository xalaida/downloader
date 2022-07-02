<?php

namespace Nevadskiy\Downloader;

use DomainException;
use Exception;
use InvalidArgumentException;
use RuntimeException;

/**
 * TODO: add custom app exceptions
 */
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

        $path = $this->getPath($path, $url);

        $this->ensureFileCanBeWritten($path);

        $this->performDownload($url, $path);
    }

    /**
     * Get a file path by the given path and URL.
     */
    protected function getPath(string $path, string $url): string
    {
        if (! is_dir($path)) {
            return $path;
        }

        return $path . DIRECTORY_SEPARATOR . basename($url);
    }

    /**
     * Get a directory from the given path.
     */
    protected function getDirectory(string $path): string
    {
        $directory = dirname($path);

        if (! is_dir($directory)) {
            throw new RuntimeException(sprintf('Directory "%s" does not exists', $directory));
        }

        return $directory;
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
            throw new RuntimeException(sprintf('A file "%s" already exists.', $path));
        }
    }

    /**
     * Apply a callback to a file stream.
     */
    private function withFileStream(string $path, callable $callback)
    {
        $stream = $this->openFileStream($path);

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
     * Perform the download process.
     */
    protected function performDownload(string $url, string $path)
    {
        $tempFile = new TempFile($this->getDirectory($path));

        try {
            $tempFile->fillUsing(function ($stream) use ($url) {
                $this->writeStreamUsingCurl($url, $stream);
            });

            $tempFile->saveAs($path);
        } catch (DomainException $e) {
            $tempFile->delete();

            throw $e;
        }
    }

    /**
     * Get a temp file path.
     */
    protected function getTempPath(string $path)
    {
        return tempnam($this->getDirectory($path), 'tmp_');
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

        if ($error) {
            throw new DomainException($error); // TODO: change exception.
        }

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

    /**
     * Mark a file as permanent.
     */
    private function markAsPermanent(string $tempPath, string $path)
    {
        @unlink($path);

        rename($tempPath, $path);
    }
}
