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
     * Default permissions for created destination directory.
     */
    const DEFAULT_DIRECTORY_PERMISSIONS = 0755;

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
    protected $createsDirectory = false;

    /**
     * Indicates if it creates destination directory recursively when it is missing.
     *
     * @var bool
     */
    protected $createsDirectoryRecursively = false;

    /**
     * Permissions of destination directory that can be created if it is missing.
     *
     * @var int
     */
    protected $directoryPermissions = self::DEFAULT_DIRECTORY_PERMISSIONS;

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
     * Create destination directory when it is missing.
     */
    public function createDirectory(bool $recursive = false, int $permissions = self::DEFAULT_DIRECTORY_PERMISSIONS): CurlDownloader
    {
        $this->createsDirectory = true;
        $this->createsDirectoryRecursively = $recursive;
        $this->directoryPermissions = $permissions;

        return $this;
    }

    /**
     * Recursively create destination directory when it is missing.
     */
    public function createDirectoryRecursively(int $permissions = self::DEFAULT_DIRECTORY_PERMISSIONS): CurlDownloader
    {
        return $this->createDirectory(true, $permissions);
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

        $directory = $this->getDirectoryByDestination($destination);
        $fileName = $this->getFileNameByDestination($destination) ?: $this->getFileNameByUrl($url);

        $path = $this->getDestinationPath($directory, $fileName);

        if ($this->shouldReturnExistingFile($path)) {
            return $path;
        }

        $this->ensureDestinationDirectoryExists($directory);

        $tempFile = new TempFile($directory);

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
     * Get a directory by the given destination.
     */
    private function getDirectoryByDestination(string $destination)
    {
        if (is_dir($destination)) {
            return $destination;
        }

        return dirname($destination);
    }

    /**
     * Get a file name by the given destination.
     *
     * @return string|null
     */
    protected function getFileNameByDestination(string $destination)
    {
        if (is_dir($destination)) {
            return null;
        }

        $fileName = basename($destination);

        if (! trim($fileName, '.')) {
            return null;
        }

        return $fileName;
    }

    /**
     * Get a file name by the given URL.
     */
    protected function getFileNameByUrl(string $url): string
    {
        return basename($url);
    }

    /**
     * Get a destination path by the given directory and file name.
     */
    protected function getDestinationPath(string $directory, string $fileName): string
    {
        return $directory . DIRECTORY_SEPARATOR . $fileName;
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
    protected function ensureDestinationDirectoryExists(string $directory)
    {
        if (is_dir($directory)) {
            return;
        }

        if (! $this->createsDirectory) {
            throw new RuntimeException(sprintf('Directory "%s" does not exist', $directory));
        }

        // TODO: specify separately mkdir directory permissions
        if (! mkdir($directory, $this->directoryPermissions, $this->createsDirectoryRecursively) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $directory));
        }
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
