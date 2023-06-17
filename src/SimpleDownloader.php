<?php

namespace Nevadskiy\Downloader;

use Nevadskiy\Downloader\Exceptions\TransferException;
use RuntimeException;
use Throwable;

class SimpleDownloader
{
    /**
     * A cURL session.
     *
     * @var false|resource
     */
    private $curl;

    /**
     * The MIME types map to file extensions.
     *
     * @var array
     */
    private $mimeTypes;

    /**
     * Make a new downloader instance.
     */
    public function __construct()
    {
        $this->mimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
        ];

        $this->init();
    }

    /**
     * Initialize a cURL session.
     */
    protected function init()
    {
        $this->curl = curl_init();

        curl_setopt($this->curl, CURLOPT_FAILONERROR, true);
    }

    /**
     * Close a cURL session.
     */
    protected function close()
    {
        if (! is_null($this->curl)) {
            curl_close($this->curl);
            $this->curl = null;
        }
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
     * Add a cURL option with the given value.
     *
     * @see: https://www.php.net/manual/en/function.curl-setopt.php
     */
    public function withCurlOption($option, $value): self
    {
        curl_setopt($this->curl, $option, $value);

        return $this;
    }

    /**
     * Add custom MIME types for extension detector.
     */
    public function mimeTypes(array $mimeTypes): self
    {
        $this->mimeTypes = array_merge($this->mimeTypes, $mimeTypes);

        return $this;
    }

    /**
     * Download a file from the URL and save to the given path.
     *
     * @throws TransferException
     */
    public function download(string $url, string $destination): string
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

        $temp = tempnam($dir, 'tmp');

        $response = $this->newFile($temp, function ($file) use ($url) {
            return $this->transfer($url, $file);
        });

        $path = $path ?: $dir . DIRECTORY_SEPARATOR . $this->guessFilename($response);

        rename($temp, $path);

        return $path;
    }

    /**
     * Write a file using the given callback.
     *
     * @template TValue
     * @param string $path
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
     * Fetch URL and write to the file.
     */
    protected function transfer(string $url, $file): array
    {
        curl_setopt_array($this->curl, [
            CURLOPT_URL => $url,
            CURLOPT_FILE => $file,
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

        try {
            $response = curl_exec($this->curl);

            if ($response === false) {
                throw new TransferException(curl_error($this->curl));
            }

            return [
                'url' => curl_getinfo($this->curl, CURLINFO_EFFECTIVE_URL),
                'mime' => $mime,
                'filename' => $filename,
            ];
        } finally {
            $this->close();
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

        $extension = $this->guessExtension($response['mime']);

        if (! $extension) {
            return $filename;
        }

        return $filename . '.' . $extension;
    }

    /**
     * Guess a file extension by the MIME type.
     *
     * @todo use specific lib for that.
     */
    protected function guessExtension(string $mime = null)
    {
        return $this->mimeTypes[$mime] ?? null;
    }

    /**
     * Generate a random filename.
     */
    protected function generateRandomFilename(): string
    {
        return md5(uniqid(mt_rand(), true));
    }

    /**
     * Destroy a downloader instance.
     */
    public function __destruct()
    {
        $this->close();
    }
}
