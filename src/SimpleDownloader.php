<?php

namespace Nevadskiy\Downloader;

use Nevadskiy\Downloader\Exceptions\TransferException;
use RuntimeException;
use Throwable;

class SimpleDownloader
{
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
     * Add custom content types for extension detector.
     */
    public function contentTypes(array $contentTypes): self
    {
        $this->contentTypes = array_merge($this->contentTypes, $contentTypes);

        return $this;
    }

    /**
     * Download a file from the URL and save to the given path.
     *
     * @throws TransferException
     */
    public function download(string $url, string $destination): string
    {
        list($dir, $path) = $this->parseDestination($destination);

        $temp = tempnam($dir, 'tmp');

        $response = $this->newFile($temp, function ($file) use ($url) {
            return $this->write($url, $file);
        });

        $path = $path ?: $dir . DIRECTORY_SEPARATOR . $this->guessFilename($response);

        rename($temp, $path);

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
     * Write a file using the given callback.
     *
     * @template TValue
     * @param callable(): TValue $writer
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
            CURLOPT_URL => $url,
            CURLOPT_FILE => $file,
            CURLOPT_FAILONERROR => true,
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
                throw new TransferException(curl_error($curl));
            }

            return [
                'url' => curl_getinfo($curl, CURLINFO_EFFECTIVE_URL),
                'content_type' => $contentType,
                'filename' => $filename,
            ];
        } finally {
            curl_close($curl);
        }
    }

    /**
     * Guess filename from the given URL.
     */
    protected function guessFilename(array $response): string
    {
        if ($response['filename']) {
            return $response['filename'];
        }

        $path = parse_url($response['url'], PHP_URL_PATH);

        $filename = pathinfo($path, PATHINFO_BASENAME) ?: $this->generateRandomFilename();

        if (pathinfo($path, PATHINFO_EXTENSION)) {
            return $filename;
        }

        $extension = $this->guessExtension($response['content_type']);

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
    protected function guessExtension(string $contentType = null)
    {
        return $this->contentTypes[$contentType] ?? null;
    }

    /**
     * Generate a random filename.
     */
    protected function generateRandomFilename(): string
    {
        return md5(uniqid(mt_rand(), true));
    }

    // @todo specify custom workdir
    // @todo relative paths
    // @todo specify custom temp dir (or system temp dir)
}
