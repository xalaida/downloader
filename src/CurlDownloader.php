<?php

namespace Nevadskiy\Downloader;

use InvalidArgumentException;
use RuntimeException;
use function dirname;
use const DIRECTORY_SEPARATOR;
use function is_int;

class CurlDownloader implements Downloader
{
    /**
     * The cURL options array.
     *
     * @var array
     */
    protected $curlOptions = [];

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
    protected $clobber = false;

    /**
     * Indicates if it creates destination directory when it is missing.
     *
     * @var bool
     */
    protected $createsDestinationDirectory = false;

    /**
     * Make a new downloader instance.
     */
    public function __construct(array $curlOptions = [])
    {
        $this->curlOptions = $this->curlOptions() + $curlOptions;
    }

    /**
     * Overwrite content when a file already exists.
     */
    public function withClobbering(): CurlDownloader
    {
        $this->clobber = true;

        return $this;
    }

    /**
     * Do not overwrite content when a file already exists.
     */
    public function withoutClobbering(): CurlDownloader
    {
        $this->clobber = false;

        return $this;
    }

    /**
     * Recursively create destination directory when it is missing.
     */
    public function createDestinationDirectory(): CurlDownloader
    {
        $this->createsDestinationDirectory = true;

        return $this;
    }

    /**
     * The default cURL options.
     */
    protected function curlOptions(): array
    {
        return [
            CURLOPT_FAILONERROR => true,
            CURLOPT_FOLLOWLOCATION => true,
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
     * Add headers to the cURL request.
     */
    public function withHeaders(array $headers)
    {
        $curlHeaders = [];

        foreach ($headers as $name => $value) {
            $curlHeaders[] = is_int($name) ? $value : "{$name}: {$value}";
        }

        $this->withCurlOption(CURLOPT_HTTPHEADER, $curlHeaders);
    }

    /**
     * @inheritdoc
     */
    public function download(string $url, string $destination): string
    {
        $this->ensureUrlIsValid($url);

        $path = $this->getDestinationPath($destination, $url);

        if ($this->shouldReturnExistingFile($path)) {
            return $path;
        }

        $tempFile = new TempFile($this->getDestinationDirectory($path));

        try {
            $tempFile->writeUsing(function ($stream) use ($url) {
                $this->writeStreamUsingCurl($url, $stream);
            });

            $tempFile->save($path);

            return $path;
        } catch (DownloaderException $e) {
            $tempFile->delete();

            throw $e;
        }
    }

    /**
     * Ensure that the given URL is valid.
     */
    protected function ensureUrlIsValid(string $url)
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException(sprintf('The URL "%s" is invalid', $url));
        }
    }

    /**
     * Get a destination file path by the given directory and URL.
     */
    protected function getDestinationPath(string $destination, string $url): string
    {
        if (! is_dir($destination)) {
            return $destination;
        }

        return $destination . DIRECTORY_SEPARATOR . $this->getFileNameByUrl($url);
    }

    /**
     * Get a file name by the given URL.
     */
    protected function getFileNameByUrl(string $url): string
    {
        return basename($url);
    }

    /**
     * Determine if it should return a file when it is already exists.
     */
    protected function shouldReturnExistingFile(string $path): bool
    {
        if (! file_exists($path)) {
            return false;
        }

        if ($this->clobber) {
            return false;
        }

        return true;
    }

    /**
     * Get a directory from the given path.
     */
    protected function getDestinationDirectory(string $path): string
    {
        $directory = dirname($path);

        if (is_dir($directory)) {
            return $directory;
        }

        if (! $this->createsDestinationDirectory) {
            throw new RuntimeException(sprintf('Directory "%s" does not exist', $directory));
        }

        if (! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $directory));
        }

        return $directory;
    }

    /**
     * Write a stream using cURL.
     *
     * @param resource $stream
     * @return string|null
     */
    protected function writeStreamUsingCurl(string $url, $stream)
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

        $error = $response === false
            ? curl_error($ch)
            : null;

        curl_close($ch);

        if ($error) {
            throw new DownloaderException($error);
        }

        return $response;
    }
}
