<?php

namespace Nevadskiy\Downloader;

use InvalidArgumentException;
use RuntimeException;

/**
 * @TODO add helper methods for most common cURL options.
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
     * @TODO consider filtering reserved options that can break curl downloading configuration.
     */
    protected function curlOptions(): array
    {
        return [
            CURLOPT_FAILONERROR => true,
        ];
    }

    /**
     * Add a cURL option with the given value.
     *
     * @return void
     */
    public function withCurlOption($option, $value)
    {
        $this->curlOptions[$option] = $value;
    }

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

        // TODO: add possibility to use path as directory and automatically define file name.

        if (! $this->overwrite && file_exists($path)) {
            throw new RuntimeException('A file "%" already exists.');
        }

        $stream = @fopen($path, 'wb+');

        if (! $stream) {
            throw new RuntimeException(sprintf('Cannot open file %s', $path));
        }

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_FILE, $stream);

        foreach ($this->curlOptions as $option => $value) {
            curl_setopt($ch, $option, $value);
        }

        foreach ($this->curlHandleCallbacks as $callback) {
            $callback($ch);
        }

        $response = curl_exec($ch);

        $error = $this->captureError($ch, $response);

        curl_close($ch);

        fclose($stream);

        if ($error) {
            unlink($path);

            throw new RuntimeException($error);
        }
    }

    /**
     * Guess the file name by the given URL.
     */
    protected function guessFileName(string $url): string
    {
        $position = strrpos($url, '/');

        if ($position === false) {
            // TODO: provide default file name (probably check file mime-type).
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
     * Ensure that the given URL is valid.
     *
     * @return void
     */
    protected function ensureUrlIsValid(string $url)
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('The given URL is invalid.');
        }
    }
}
