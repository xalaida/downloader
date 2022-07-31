<?php

namespace Nevadskiy\Downloader;

use InvalidArgumentException;
use Nevadskiy\Downloader\Exceptions\DownloaderException;
use Nevadskiy\Downloader\Exceptions\FileNotModifiedException;
use Nevadskiy\Downloader\Exceptions\NetworkException;
use RuntimeException;
use function dirname;
use const DIRECTORY_SEPARATOR;

class CurlDownloader implements Downloader
{
    /**
     * Throw an exception if the file already exists.
     */
    const CLOBBER_MODE_FAIL = 0;

    /**
     * Skip downloading if the file already exists.
     */
    const CLOBBER_MODE_SKIP = 1;

    /**
     * Update contents if the existing file is different from the downloaded one.
     */
    const CLOBBER_MODE_UPDATE = 2;

    /**
     * Replace contents if file already exists.
     */
    const CLOBBER_MODE_REPLACE = 3;

    /**
     * Default permissions for created destination directory.
     */
    const DEFAULT_DIRECTORY_PERMISSIONS = 0755;

    /**
     * The cURL request headers.
     */
    protected $headers = [];

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
     * Specifies how the downloader should handle a file that already exists.
     *
     * @var int
     */
    protected $clobberMode = self::CLOBBER_MODE_SKIP;

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
     * Throw an exception if the file already exists.
     */
    public function failIfExists(): CurlDownloader
    {
        $this->clobberMode = self::CLOBBER_MODE_FAIL;

        return $this;
    }

    /**
     * Skip downloading if the file already exists.
     */
    public function skipIfExists(): CurlDownloader
    {
        $this->clobberMode = self::CLOBBER_MODE_SKIP;

        return $this;
    }

    /**
     * Update contents if the existing file is different from the downloaded one.
     */
    public function updateIfExists(): CurlDownloader
    {
        $this->clobberMode = self::CLOBBER_MODE_UPDATE;

        return $this;
    }

    /**
     * Replace contents if file already exists.
     */
    public function replaceIfExists(): CurlDownloader
    {
        $this->clobberMode = self::CLOBBER_MODE_REPLACE;

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
        foreach ($headers as $name => $value) {
            $this->headers[$name] = $value;
        }

        return $this;
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

        // TODO: remove double "file_exists" check.
        $headers = file_exists($path) && $this->clobberMode === self::CLOBBER_MODE_UPDATE ? $this->getLastModificationHeader($path) : [];

        try {
            $this->performDownload($path, $url, $headers);

            return $this->normalizePath($path);
        } catch (FileNotModifiedException $e) {
            return $this->normalizePath($path);
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

        if ($this->clobberMode === self::CLOBBER_MODE_FAIL) {
            throw new RuntimeException(sprintf('File "%s" already exists', $path));
        }

        if ($this->clobberMode === self::CLOBBER_MODE_SKIP) {
            return true;
        }

        return false;
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
    protected function writeStreamUsingCurl($stream, string $url, array $headers = [])
    {
        $ch = curl_init($url);

        // TODO: make this option reserved.
        curl_setopt($ch, CURLOPT_FILE, $stream);

        // TODO: make this option reserved.
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->normalizeHeaders(array_merge($this->headers, $headers)));

        curl_setopt_array($ch, $this->curlOptions);

        foreach ($this->curlHandleCallbacks as $handleCallbacks) {
            $handleCallbacks($ch);
        }

        $response = curl_exec($ch);

        // TODO: refactor error structure.
        $error = $response === false
            ? new NetworkException(curl_error($ch))
            : null;

        // TODO: use 304 response code constant.
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 304) {
            // TODO: refactor error structure.
            $error = new FileNotModifiedException();
        }

        curl_close($ch);

        if ($error) {
            throw $error;
        }

        return $response;
    }

    /**
     * Normalize headers for cURL instance.
     */
    protected function normalizeHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $name => $value) {
            $normalized[] = "{$name}: {$value}";
        }

        return $normalized;
    }

    /**
     * Perform the file download process to the given path using the given url and headers.
     */
    protected function performDownload(string $path, string $url, array $headers)
    {
        $directory = dirname($path);

        $this->ensureDestinationDirectoryExists($directory);

        $tempFile = new TempFile($directory);

        try {
            $tempFile->writeUsing(function ($stream) use ($url, $headers) {
                $this->writeStreamUsingCurl($stream, $url, $headers);
            });

            $tempFile->save($path);
        } catch (DownloaderException $e) {
            $tempFile->delete();

            throw $e;
        }
    }

    /**
     * Normalize the path of the downloaded file.
     */
    protected function normalizePath(string $path): string
    {
        return realpath($path);
    }

    /**
     * Get the last modification header.
     */
    protected function getLastModificationHeader(string $path): array
    {
        return ['If-Modified-Since' => gmdate('D, d M Y H:i:s T', filemtime($path))];
    }
}
