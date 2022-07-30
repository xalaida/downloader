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
     * Indicates the base directory to use to create the destination path.
     *
     * @var string
     */
    protected $baseDirectory;

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
     * Specify the base directory to use to create the destination path.
     */
    public function baseDirectory(string $directory): CurlDownloader
    {
        $this->baseDirectory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

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
    public function withCurlOption($option, $value): CurlDownloader
    {
        $this->curlOptions[$option] = $value;

        return $this;
    }

    /**
     * Add a cURL handle callback.
     */
    public function withCurlHandle(callable $callback): CurlDownloader
    {
        $this->curlHandleCallbacks[] = $callback;

        return $this;
    }

    /**
     * Add headers to the cURL request.
     */
    public function withHeaders(array $headers): CurlDownloader
    {
        $curlHeaders = [];

        foreach ($headers as $name => $value) {
            $curlHeaders[] = is_int($name) ? $value : "{$name}: {$value}";
        }

        return $this->withCurlOption(CURLOPT_HTTPHEADER, $curlHeaders);
    }

    /**
     * @inheritdoc
     */
    public function download(string $url, string $destination = './'): string
    {
        $this->ensureUrlIsValid($url);

        $path = $this->getDestinationPath($url, $destination);

        if ($this->shouldReturnExistingFile($path)) {
            return $path;
        }

        $directory = dirname($path);

        $this->ensureDestinationDirectoryExists($directory);

        $tempFile = new TempFile($directory);

        try {
            $tempFile->writeUsing(function ($stream) use ($url) {
                $this->writeStreamUsingCurl($url, $stream);
            });

            $tempFile->save($path);

            return realpath($path);
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
     * Get a destination path of the downloaded file.
     */
    protected function getDestinationPath(string $url, string $destination): string
    {
        $destination = $this->getDestinationInBaseDirectory(rtrim($destination, '.'));

        if (! $this->isDirectory($destination)) {
            return $destination;
        }

        return rtrim($destination, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->getFileNameByUrl($url);
    }

    /**
     * Get a destination path according to the base directory.
     */
    protected function getDestinationInBaseDirectory(string $destination): string
    {
        if (! $this->baseDirectory) {
            return $destination;
        }

        return $this->baseDirectory . ltrim($destination, DIRECTORY_SEPARATOR . '.');
    }

    /**
     * Determine if the given destination is a directory.
     */
    protected function isDirectory(string $destination): bool
    {
        return is_dir($destination) || mb_substr($destination, -1) === DIRECTORY_SEPARATOR;
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
    protected function ensureDestinationDirectoryExists(string $directory)
    {
        if (is_dir($directory)) {
            return;
        }

        if (! $this->createsDirectory) {
            throw new RuntimeException(sprintf('Directory "%s" does not exist', $directory));
        }

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
