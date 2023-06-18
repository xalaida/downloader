<?php

namespace Nevadskiy\Downloader;

use Nevadskiy\Downloader\Exceptions\DestinationFileMissingException;
use Nevadskiy\Downloader\Exceptions\DownloaderException;
use Nevadskiy\Downloader\Exceptions\FileExistsException;
use Nevadskiy\Downloader\Exceptions\NotModifiedResponseException;
use Nevadskiy\Downloader\Filename\FilenameGenerator;
use Nevadskiy\Downloader\Filename\Md5FilenameGenerator;
use Nevadskiy\Downloader\Filename\TempFilenameGenerator;
use RuntimeException;
use Throwable;

class SimpleDownloader
{
    /**
     * Throw an exception if the file already exists.
     */
    const CLOBBERING_FAIL = 0;

    /**
     * Skip downloading if the file already exists.
     */
    const CLOBBERING_SKIP = 1;

    // @todo clone if exists (hello-world.txt => hello-world.txt.1).

    // @todo update if exists (last-modified header and if-modified-since header). Check cURL CURLOPT_FILETIME options.

    /**
     * Replace contents if file already exists.
     */
    const CLOBBERING_REPLACE = 2;

    /**
     * Update contents if file exists and is older than downloading one.
     */
    const CLOBBERING_UPDATE = 3;

    /**
     * Indicates how the downloader should handle a file that already exists.
     *
     * @var int
     */
    protected $clobbering = self::CLOBBERING_FAIL;

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
     * The MIME types map to file extensions.
     *
     * @var array
     */
    protected $contentTypes = [];

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
     * Make a new downloader instance.
     */
    public function __construct()
    {
        $this->contentTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
        ];

        $this->tempFilenameGenerator = new TempFilenameGenerator();
        $this->randomFilenameGenerator = new Md5FilenameGenerator();
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
     * Include the given headers to the cURL request.
     */
    public function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);

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
     * Download a file from the URL and save to the given path.
     *
     * @throws DownloaderException
     */
    public function download(string $url, string $destination): string
    {
        list($dir, $path) = $this->parseDestination($destination);

        if ($this->clobbering === self::CLOBBERING_UPDATE) {
            $this->includeTimestamps($path);
        }

        $tempPath = $dir . DIRECTORY_SEPARATOR . $this->tempFilenameGenerator->generate();

        try {
            $response = $this->newFile($tempPath, function ($file) use ($url) {
                return $this->write($url, $file);
            });
        } catch (NotModifiedResponseException $e) {
            return $path;
        }

        $path = $path ?: $dir . DIRECTORY_SEPARATOR . $this->guessFilename($response);

        $this->saveAs($tempPath, $path);

        return $path;
    }

    /**
     * Parse destination to retrieve a directory and destination path.
     */
    protected function parseDestination(string $destination): array
    {
        if (is_dir($destination)) {
            $dir = $destination;
            $path = null;
        } else {
            $dir = dirname($destination);
            $path = $destination;

            if (! is_dir($dir)) {
                throw new RuntimeException(sprintf('Directory [%s] is missing.', $dir));
            }
        }

        return [$dir, $path];
    }

    /**
     * Use the file timestamps in the cURL request.
     */
    protected function includeTimestamps(string $path = null)
    {
        if ($path === null) {
            throw DestinationFileMissingException::new();
        } else if (file_exists($path)) {
            $this->withHeaders($this->getIfModifiedSinceHeader($path));
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
            // CURLOPT_FILETIME => true,
            CURLOPT_HTTPHEADER => $this->buildHeaders(),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 20,
            CURLOPT_HEADERFUNCTION => function ($curl, $header) use (&$filename, &$contentType) {
                if (stripos($header, 'Content-Disposition: attachment') !== false) {
                    preg_match('/filename="(.+)"/', $header, $matches);
                    if (isset($matches[1])) {
                        $filename = $matches[1];
                    }
                }

                if (stripos($header, 'Content-Type: ') !== false) {
                    $contentType = trim(str_ireplace('Content-Type: ', '', $header));
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
                throw new NotModifiedResponseException();
            }

            return [
                'filename' => $filename,
                'content_type' => $contentType,
                'url' => curl_getinfo($curl, CURLINFO_EFFECTIVE_URL),
                // 'filetime' => curl_getinfo($curl, CURLINFO_FILETIME),
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
     * Guess a file extension by the content type.
     *
     * @todo use specific lib for that.
     */
    protected function guessExtension(array $response)
    {
        return $this->contentTypes[$response['content_type']] ?? null;
    }

    /**
     * Save a temp file to the given path.
     */
    protected function saveAs(string $tempPath, string $path)
    {
        if (! file_exists($path)) {
            rename($tempPath, $path);
        } else if ($this->clobbering === self::CLOBBERING_FAIL) {
            unlink($tempPath);

            throw FileExistsException::from($path);
        } else if ($this->clobbering === self::CLOBBERING_SKIP) {
            unlink($tempPath);
        } else if ($this->clobbering === self::CLOBBERING_REPLACE) {
            rename($tempPath, $path);
        } else if ($this->clobbering === self::CLOBBERING_UPDATE) {
            rename($tempPath, $path);
        }
    }

    /**
     * Add content types for extension detector.
     */
    public function withContentTypes(array $contentTypes): self
    {
        $this->contentTypes = array_merge($this->contentTypes, $contentTypes);

        return $this;
    }

    /**
     * Set the temp filename generator.
     */
    public function setTempFilenameGenerator(FilenameGenerator $generator): self
    {
        $this->tempFilenameGenerator = $generator;

        return $this;
    }

    /**
     * Set the random filename generator.
     */
    public function setRandomFilenameGenerator(FilenameGenerator $generator): self
    {
        $this->randomFilenameGenerator = $generator;

        return $this;
    }
}
