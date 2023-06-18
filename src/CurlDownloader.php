<?php

namespace Nevadskiy\Downloader;

use Nevadskiy\Downloader\Exceptions\FilenameMissingException;
use Nevadskiy\Downloader\Exceptions\DirectoryMissingException;
use Nevadskiy\Downloader\Exceptions\DownloaderException;
use Nevadskiy\Downloader\Exceptions\FileExistsException;
use Nevadskiy\Downloader\Exceptions\ResponseNotModifiedException;
use Nevadskiy\Downloader\ExtensionGuesser\ExtensionGuesser;
use Nevadskiy\Downloader\ExtensionGuesser\SymfonyExtensionGuesser;
use Nevadskiy\Downloader\FilenameGenerator\FilenameGenerator;
use Nevadskiy\Downloader\FilenameGenerator\Md5FilenameGenerator;
use Nevadskiy\Downloader\FilenameGenerator\TempFilenameGenerator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

class CurlDownloader implements Downloader, LoggerAwareInterface
{
    /**
     * Throw an exception if the file already exists.
     */
    public const CLOBBERING_FAIL = 0;

    /**
     * Skip downloading if the file already exists.
     */
    public const CLOBBERING_SKIP = 1;
    /**
     * Replace contents if file already exists.
     */
    public const CLOBBERING_REPLACE = 2;

    /**
     * Update contents if file exists and is older than downloading one.
     */
    public const CLOBBERING_UPDATE = 3;

    /**
     * Default permissions for created destination directory.
     */
    public const DEFAULT_DIRECTORY_PERMISSIONS = 0755;

    /**
     * Indicates how the downloader should handle a file that already exists.
     *
     * @var int
     */
    protected $clobbering = self::CLOBBERING_FAIL;

    /**
     * Indicates if it makes a destination directory when it is missing.
     *
     * @var bool
     */
    protected $makesDirectory = false;

    /**
     * Permissions of a destination directory that can be made if it is missing.
     *
     * @var int
     */
    protected $directoryPermissions = self::DEFAULT_DIRECTORY_PERMISSIONS;

    /**
     * Indicates if it makes a destination directory recursively when it is missing.
     *
     * @var bool
     */
    protected $makesDirectoryRecursively = false;

    /**
     * The header list to be included in the cURL request.
     */
    protected $headers = [];

    /**
     * The cURL callbacks.
     *
     * @var array
     */
    protected $curlCallbacks = [];

    /**
     * The extension guesser instance.
     *
     * @var ExtensionGuesser
     */
    protected $extensionGuesser;

    /**
     * The temp filename generator.
     *
     * @var FilenameGenerator
     */
    protected $tempFilenameGenerator;

    /**
     * The random filename generator.
     *
     * @var FilenameGenerator
     */
    protected $randomFilenameGenerator;

    /**
     * The logger interface.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Make a new downloader instance.
     */
    public function __construct()
    {
        $this->extensionGuesser = new SymfonyExtensionGuesser();
        $this->tempFilenameGenerator = new TempFilenameGenerator();
        $this->randomFilenameGenerator = new Md5FilenameGenerator();
        $this->logger = new NullLogger();
    }

    /**
     * Throw an exception if a file already exists.
     */
    public function failIfExists(): self
    {
        $this->clobbering = self::CLOBBERING_FAIL;

        return $this;
    }

    /**
     * Skip downloading if a file already exists.
     */
    public function skipIfExists(): self
    {
        $this->clobbering = self::CLOBBERING_SKIP;

        return $this;
    }

    /**
     * Replace contents if file already exists.
     */
    public function replaceIfExists(): self
    {
        $this->clobbering = self::CLOBBERING_REPLACE;

        return $this;
    }

    /**
     * Update contents if the existing file is older than downloading one.
     */
    public function updateIfExists(): self
    {
        $this->clobbering = self::CLOBBERING_UPDATE;

        return $this;
    }

    /**
     * Make a destination directory when it is missing.
     */
    public function allowDirectoryCreation(int $permissions = self::DEFAULT_DIRECTORY_PERMISSIONS, bool $recursive = false): self
    {
        $this->makesDirectory = true;
        $this->directoryPermissions = $permissions;
        $this->makesDirectoryRecursively = $recursive;

        return $this;
    }

    /**
     * Recursively make a destination directory when it is missing.
     */
    public function allowRecursiveDirectoryCreation(int $permissions = self::DEFAULT_DIRECTORY_PERMISSIONS): self
    {
        return $this->allowDirectoryCreation($permissions, true);
    }

    /**
     * Include the given headers to the cURL request.
     */
    public function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    /**
     * Specify the progress callback.
     */
    public function withProgress(callable $callback): self
    {
        $this->withCurlOption(CURLOPT_NOPROGRESS, false);

        $this->withCurlOption(CURLOPT_PROGRESSFUNCTION, function ($ch, int $download, int $downloaded, int $upload, int $uploaded) use ($callback) {
            $callback($download, $downloaded, $upload, $uploaded);
        });

        return $this;
    }

    /**
     * Register the callback for a cURL session.
     *
     * @see https://www.php.net/manual/en/function.curl-setopt.php
     */
    public function withCurl(callable $callback): self
    {
        $this->curlCallbacks[] = $callback;

        return $this;
    }

    /**
     * Specify a cURL option with the given value.
     *
     * @see https://www.php.net/manual/en/function.curl-setopt.php
     */
    public function withCurlOption($option, $value): self
    {
        return $this->withCurl(function ($curl) use ($option, $value) {
            curl_setopt($curl, $option, $value);
        });
    }

    /**
     * @inheritdoc
     */
    public function download(string $url, string $destination = null): string
    {
        [$directory, $path] = $this->parseDestination($destination);

        if ($this->clobbering === self::CLOBBERING_UPDATE) {
            $this->includeTimestamp($path);
        }

        $tempPath = $directory . $this->tempFilenameGenerator->generate();

        try {
            $response = $this->newFile($tempPath, function ($file) use ($url) {
                return $this->write($url, $file);
            });
        } catch (ResponseNotModifiedException $e) {
            return $path;
        }

        $path = $path ?: $directory . $this->guessFilename($response);

        $this->saveAs($tempPath, $path, $response);

        return $path;
    }

    /**
     * Parse destination to retrieve a directory and destination path.
     */
    protected function parseDestination(string $destination = null): array
    {
        if (is_null($destination)) {
            return ['', null];
        }

        if ($this->isDirectory($destination)) {
            $directory = rtrim($destination, DIRECTORY_SEPARATOR . '.');
            $path = null;
        } else {
            $directory = dirname($destination);
            $path = $destination;
        }

        $this->makeDirectoryIfMissing($directory);

        return [$directory . DIRECTORY_SEPARATOR, $path];
    }

    /**
     * Determine whether the destination path is a directory.
     */
    protected function isDirectory(string $destination): bool
    {
        return is_dir($destination)
            || mb_substr($destination, -1) === DIRECTORY_SEPARATOR
            || mb_substr($destination, -2) === DIRECTORY_SEPARATOR . '.';
    }

    /**
     * Make a destination directory if it is missing.
     */
    protected function makeDirectoryIfMissing(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (! $this->makesDirectory) {
            throw DirectoryMissingException::from($directory);
        }

        $this->logger->notice('Creating missing directory.', [
            'directory' => $directory,
        ]);

        mkdir($directory, $this->directoryPermissions, $this->makesDirectoryRecursively);
    }

    /**
     * Use the file timestamps in the cURL request.
     */
    protected function includeTimestamp(string $path = null): void
    {
        if ($path === null) {
            throw FilenameMissingException::new();
        } elseif (file_exists($path)) {
            $this->logger->debug('File exists. Including "If-Modified-Since" header with timestamp.', [
                'path' => $path,
            ]);

            $this->withHeaders($this->getIfModifiedSinceHeader($path));
        } else {
            $this->logger->debug('File is missing. Skipping "If-Modified-Since" header with timestamp.', [
                'path' => $path,
            ]);
        }
    }

    /**
     * Get the "If-Modified-Since" header for the given file path.
     */
    protected function getIfModifiedSinceHeader(string $path): array
    {
        return ['If-Modified-Since' => gmdate('D, d M Y H:i:s T', filemtime($path))];
    }

    /**
     * Write a file using the given callback.
     *
     * @template TValue
     * @param callable(resource $file): TValue $writer
     * @return TValue
     */
    protected function newFile(string $path, callable $writer)
    {
        $file = fopen($path, 'wb');

        try {
            return $writer($file);
        } catch (Throwable $e) {
            unlink($path);

            throw $e;
        } finally {
            fclose($file);
        }
    }

    /**
     * Get content by URL and write to the file.
     *
     * @param resource $file
     */
    protected function write(string $url, $file): array
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_FAILONERROR => true,
            CURLOPT_URL => $url,
            CURLOPT_FILE => $file,
            CURLOPT_FILETIME => true,
            CURLOPT_HTTPHEADER => $this->buildHeaders(),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 20,
            CURLOPT_HEADERFUNCTION => function ($curl, $header) use (&$filename, &$mime) {
                if (stripos($header, 'Content-Disposition: attachment') !== false) {
                    preg_match('/filename="(.+)"/', $header, $matches);
                    if (isset($matches[1])) {
                        $filename = $matches[1];
                    }
                }

                if (stripos($header, 'Content-Type: ') !== false) {
                    $mime = trim(str_ireplace('Content-Type: ', '', $header));
                }

                return strlen($header);
            }
        ]);

        foreach ($this->curlCallbacks as $callback) {
            $callback($curl);
        }

        try {
            $response = curl_exec($curl);

            if ($response === false) {
                throw new DownloaderException(curl_error($curl));
            }

            if (curl_getinfo($curl, CURLINFO_HTTP_CODE) === 304) {
                throw new ResponseNotModifiedException();
            }

            return [
                'filename' => $filename,
                'mime' => $mime,
                'url' => curl_getinfo($curl, CURLINFO_EFFECTIVE_URL),
                'filetime' => curl_getinfo($curl, CURLINFO_FILETIME),
            ];
        } finally {
            curl_close($curl);
        }
    }

    /**
     * Build headers for the cURL request.
     */
    protected function buildHeaders(): array
    {
        $headers = [];

        foreach ($this->headers as $key => $value) {
            if (! is_int($key)) {
                $headers[] = "{$key}: {$value}";
            } else {
                $headers[] = $value;
            }
        }

        return $headers;
    }

    /**
     * Guess a filename from the given URL.
     */
    protected function guessFilename(array $response): string
    {
        if ($response['filename']) {
            return $response['filename'];
        }

        $path = parse_url($response['url'], PHP_URL_PATH);

        $filename = pathinfo($path, PATHINFO_BASENAME) ?: $this->randomFilenameGenerator->generate();

        if (pathinfo($path, PATHINFO_EXTENSION)) {
            return $filename;
        }

        $extension = $this->guessExtension($response);

        if (! $extension) {
            return $filename;
        }

        return $filename . '.' . $extension;
    }

    /**
     * Guess file extension from response.
     */
    protected function guessExtension(array $response): ?string
    {
        $mime = $response['mime'];

        if (! $mime) {
            return null;
        }

        return $this->extensionGuesser->getExtension($mime);
    }

    /**
     * Save a temp file to the given path.
     */
    protected function saveAs(string $tempPath, string $path, array $response): void
    {
        if (! file_exists($path)) {
            $this->logger->debug('Saving downloaded file.', [
                'tempPath' => $tempPath,
                'path' => $path,
            ]);

            rename($tempPath, $path);
        } elseif ($this->clobbering === self::CLOBBERING_FAIL) {
            $this->logger->debug('File already exists, failing.', [
                'path' => $path,
                'tempPath' => $tempPath,
            ]);

            unlink($tempPath);

            throw FileExistsException::from($path);
        } elseif ($this->clobbering === self::CLOBBERING_SKIP) {
            $this->logger->debug('File already exists, skipping.', [
                'tempPath' => $tempPath,
                'path' => $path,
            ]);

            unlink($tempPath);
        } elseif ($this->clobbering === self::CLOBBERING_REPLACE) {
            $this->logger->debug('File already exists, replacing.', [
                'tempPath' => $tempPath,
                'path' => $path,
            ]);

            rename($tempPath, $path);
        } elseif ($this->clobbering === self::CLOBBERING_UPDATE) {
            if ($response['filetime'] === -1 || $filetime = filemtime($path) < $response['filetime']) {
                $this->logger->debug('File already exists with older timestamp, replacing.', [
                    'tempPath' => $tempPath,
                    'path' => $path,
                    'tempFiletime' => $response['filetime'],
                    'filetime' => $filetime,
                ]);

                rename($tempPath, $path);
            } else {
                $this->logger->debug('File already exists with newer timestamp, skipping.', [
                    'tempPath' => $tempPath,
                    'path' => $path,
                    'tempFiletime' => $response['filetime'],
                    'filetime' => $filetime,
                ]);

                unlink($tempPath);
            }
        }
    }

    /**
     * Set the extension guesser instance.
     */
    public function setExtensionGuesser(ExtensionGuesser $guesser): self
    {
        $this->extensionGuesser = $guesser;

        return $this;
    }

    /**
     * Set the temp filename generator instance.
     */
    public function setTempFilenameGenerator(FilenameGenerator $generator): self
    {
        $this->tempFilenameGenerator = $generator;

        return $this;
    }

    /**
     * Set the random filename generator instance.
     */
    public function setRandomFilenameGenerator(FilenameGenerator $generator): self
    {
        $this->randomFilenameGenerator = $generator;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
