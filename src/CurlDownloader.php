<?php

namespace Nevadskiy\Downloader;

use InvalidArgumentException;
use Nevadskiy\Downloader\Exceptions\DirectoryMissingException;
use Nevadskiy\Downloader\Exceptions\FileExistsException;
use Nevadskiy\Downloader\Exceptions\ResponseNotModifiedException;
use Nevadskiy\Downloader\Exceptions\NetworkException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use RuntimeException;
use function dirname;
use const DIRECTORY_SEPARATOR;

class CurlDownloader implements Downloader, LoggerAwareInterface
{
    use LoggerAwareTrait;

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
     * Make a new downloader instance.
     */
    public function __construct(array $curlOptions = [])
    {
        $this->curlOptions = $curlOptions;
        $this->logger = new NullLogger();
    }

    /**
     * Throw an exception if the file already exists.
     */
    public function failIfExists(): self
    {
        $this->clobberMode = self::CLOBBER_MODE_FAIL;

        return $this;
    }

    /**
     * Skip downloading if the file already exists.
     */
    public function skipIfExists(): self
    {
        $this->clobberMode = self::CLOBBER_MODE_SKIP;

        return $this;
    }

    /**
     * Update contents if the existing file is different from the downloaded one.
     */
    public function updateIfExists(): self
    {
        $this->clobberMode = self::CLOBBER_MODE_UPDATE;

        return $this;
    }

    /**
     * Replace contents if file already exists.
     */
    public function replaceIfExists(): self
    {
        $this->clobberMode = self::CLOBBER_MODE_REPLACE;

        return $this;
    }

    /**
     * Create destination directory when it is missing.
     */
    public function allowDirectoryCreation(bool $recursive = false, int $permissions = self::DEFAULT_DIRECTORY_PERMISSIONS): self
    {
        $this->createsDirectory = true;
        $this->createsDirectoryRecursively = $recursive;
        $this->directoryPermissions = $permissions;

        return $this;
    }

    /**
     * Recursively create destination directory when it is missing.
     */
    public function allowRecursiveDirectoryCreation(int $permissions = self::DEFAULT_DIRECTORY_PERMISSIONS): self
    {
        return $this->allowDirectoryCreation(true, $permissions);
    }

    /**
     * Add a cURL option with the given value.
     *
     * @see: https://www.php.net/manual/en/function.curl-setopt.php
     */
    public function withCurlOption($option, $value): self
    {
        $this->curlOptions[$option] = $value;

        return $this;
    }

    /**
     * Add a cURL handle callback.
     */
    public function withCurlHandle(callable $callback): self
    {
        $this->curlHandleCallbacks[] = $callback;

        return $this;
    }

    /**
     * Add headers to the cURL request.
     */
    public function withHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->headers[$name] = $value;
        }

        return $this;
    }

    /**
     * Follow redirects that the server sends as a "Location" header.
     */
    public function followRedirects(int $maxRedirects = 20): self
    {
        $this->withCurlOption(CURLOPT_FOLLOWLOCATION, $maxRedirects !== 0);
        $this->withCurlOption(CURLOPT_MAXREDIRS, $maxRedirects);

        return $this;
    }

    /**
     * Specify the progress callback.
     */
    public function onProgress(callable $callback): self
    {
        $this->withCurlOption(CURLOPT_NOPROGRESS, false);

        $this->withCurlOption(CURLOPT_PROGRESSFUNCTION, function ($ch, int $total, int $loaded) use ($callback) {
            $callback($total, $loaded);
        });

        return $this;
    }

//    /**
//     * @inheritdoc
//     */
//    public function download(string $url, string $destination = null): string
//    {
//        $path = $this->getDestinationPath($url, $destination ?: '.' . DIRECTORY_SEPARATOR);
//
//        $this->performDownload($path, $url);
//
//        return $this->normalizePath($path);
//    }

    public function download(string $url, string $destination = null): string
    {
        $destination = $destination ? rtrim($destination, '.') : null;

        if ($this->isDirectory($destination)) {
            $directory = $destination;
            $filename = null;
        } else {
            $directory = dirname($destination);
            $filename = basename($destination);
        }

        $directory = $this->createDirectoryIfMissing($directory);

        $temp = tempnam($directory ?: sys_get_temp_dir(), 'downloads');
        $stream = fopen($temp, 'wb');

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_FAILONERROR => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_FILE => $stream,
            CURLOPT_HEADERFUNCTION => function ($curl, $header) use (&$responseFilename, &$mimeType) {
                if (stripos($header, 'Content-Disposition: attachment') !== false) {
                    preg_match('/filename="(.+)"/', $header, $matches);
                    if (isset($matches[1])) {
                        $responseFilename = $matches[1];
                    }
                }

                if (stripos($header, 'Content-Type: ') !== false) {
                    $mimeType = trim(str_ireplace('Content-Type: ', '', $header));
                }

                // @todo check here if file already exists, etc.
                // @todo check how many times this function have been actually called.

                return strlen($header);
            }
        ]);

        $response = curl_exec($curl);

        $error = curl_error($curl);

        $url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);

        curl_close($curl);

        fclose($stream);

        // die(var_dump($response, $error));

        if ($response === false) {
            unlink($temp);

            throw new NetworkException($error);
        }

        $filename = $filename ?: $responseFilename ?: $this->getFilename($url, $mimeType);

        // @todo process relative paths.

        $path = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        // die(var_dump($path));

//        if (file_exists($path)) {
//            if (self::CLOBBER_MODE_FAIL === $this->clobberMode) {
//                throw new FileExistsException($path);
//            } else if (self::CLOBBER_MODE_SKIP === $this->clobberMode) {
//                unlink($temp);
//
//                return $path;
//            }
//        }

        if (! rename($temp, $path)) {
            throw new RuntimeException('Unable to rename the file.');
        }

        return realpath($path);
    }

    private function getFilename(string $url, string $mimeType = null): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        $filename = pathinfo($path, PATHINFO_BASENAME) ?: $this->generateRandomFilename();

        if (pathinfo($path, PATHINFO_EXTENSION)) {
            return $filename;
        }

        $extension = $this->guessExtensionByMimeType($mimeType);

        if (! $extension) {
            return $filename;
        }

        return $filename . '.' . $extension;
    }

    // Generate a random filename. Use the server address so that a file
    // generated at the same time on a different server won't have a collision.
    // $name = md5(uniqid(empty($_SERVER['SERVER_ADDR']) ? '' : $_SERVER['SERVER_ADDR'], true));
    private function generateRandomFilename(): string
    {
        return md5(uniqid(mt_rand(), true));
    }

    private function guessExtensionByMimeType(string $mimeType)
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
            // Add more MIME types and extensions as needed
        ];

        return $extensions[$mimeType] ?? null;
    }

    /**
     * Determine if the given destination is a directory.
     */
    protected function isDirectory(string $destination): bool
    {
        return is_dir($destination)
            || mb_substr($destination, -1) === DIRECTORY_SEPARATOR;
    }

    /**
     * Perform the file download process to the given path using the given url and headers.
     */
    protected function performDownload(string $path, string $url, array $headers = [])
    {
        $this->logger->info('Downloading file "{url}" to destination "{path}"', [
            'url' => $url,
            'path' => $path
        ]);

        try {
            $this->ensureFileNotExists($path);
        } catch (FileExistsException $e) {
            if ($this->clobberMode === self::CLOBBER_MODE_FAIL) {
                throw $e;
            }

            if ($this->clobberMode === self::CLOBBER_MODE_SKIP) {
                $this->logger->notice('File "{file}" already exists, skip downloading', ['file' => $path]);

                return;
            }

            if ($this->clobberMode === self::CLOBBER_MODE_UPDATE) {
                $this->logger->notice('File "{file}" already exists, trying to update', ['file' => $path]);

                $headers = array_merge($headers, $this->getLastModificationHeader($path));
            }
        }

        try {
            $this->writeStream($path, $url, $headers);

            $this->logger->info('File "{url}" downloaded to destination "{path}"', [
                'url' => $url,
                'path' => $path
            ]);
        } catch (ResponseNotModifiedException $e) {
            $this->logger->notice('Remote file "{url}" has not been modified since the last time it was accessed', [
                'file' => $path,
                'url' => $url,
                'headers' => $headers
            ]);

            return;
        }
    }

    /**
     * Ensure that file not exists at the given path.
     */
    protected function ensureFileNotExists(string $path)
    {
        if (file_exists($path)) {
            throw new FileExistsException($path);
        }
    }

    /**
     * Get the last modification header.
     */
    protected function getLastModificationHeader(string $path): array
    {
        return ['If-Modified-Since' => gmdate('D, d M Y H:i:s T', filemtime($path))];
    }

    /**
     * Write a stream using the URL and HTTP headers to the given path.
     */
    protected function writeStream(string $path, string $url, array $headers)
    {
        $tempFile = new TempFile($this->createDirectoryIfMissing($path));

        try {
            $tempFile->writeUsing(function ($stream) use ($url, $headers) {
                $this->writeStreamUsingCurl($stream, $url, $headers);
            });

            $tempFile->save($path);
        } catch (NetworkException $e) {
            $tempFile->delete();

            throw $e;
        }
    }

    /**
     * Get the destination directory by the given file path.
     */
    protected function createDirectoryIfMissing(string $directory): string
    {
        try {
            $this->ensureDirectoryExists($directory);
        } catch (DirectoryMissingException $e) {
            if (! $this->createsDirectory) {
                throw $e;
            }

            $this->logger->notice('Creating missing directory "{directory}".', ['directory' => $directory]);

            $this->createDirectory($directory);
        }

        return $directory;
    }

    /**
     * Ensure that the directory exists at the given path.
     */
    protected function ensureDirectoryExists(string $path)
    {
        if (! is_dir($path)) {
            throw new DirectoryMissingException($path);
        }
    }

    /**
     * Create a directory using the given path.
     */
    protected function createDirectory(string $path)
    {
        mkdir($path, $this->directoryPermissions, $this->createsDirectoryRecursively);
    }

    /**
     * Write a stream using cURL.
     *
     * @param resource $stream
     */
    protected function writeStreamUsingCurl($stream, string $url, array $headers = [])
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_FILE, $stream);

        curl_setopt($ch, CURLOPT_FAILONERROR, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->normalizeHeaders(array_merge($this->headers, $headers)));

        curl_setopt_array($ch, $this->curlOptions);

        foreach ($this->curlHandleCallbacks as $handleCallbacks) {
            $handleCallbacks($ch);
        }

        try {
            $response = curl_exec($ch);

            if ($response === false) {
                throw new NetworkException(curl_error($ch));
            }

            if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 304) {
                throw new ResponseNotModifiedException();
            }
        } finally {
            curl_close($ch);
        }
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
     * Normalize the path of the downloaded file.
     */
    protected function normalizePath(string $path): string
    {
        return realpath($path);
    }
}
