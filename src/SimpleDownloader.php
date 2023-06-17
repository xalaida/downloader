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
     * Make a new downloader instance.
     */
    public function __construct()
    {
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
     * Download a file from the URL and save to the given path.
     */
    public function download(string $url, string $destination)
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

        $this->newFile($temp, function ($file) use ($url) {
            $this->transfer($url, $file);
        });

        $path = $path ?: $dir . DIRECTORY_SEPARATOR . $this->guessFilename($url);

        rename($temp, $path);

        return $path;
    }

    /**
     * Write a file using the given callback.
     */
    protected function newFile(string $path, callable $writer)
    {
        $file = fopen($path, 'wb');

        try {
            $writer($file);
        } catch (Throwable $e) {
            unlink($path);

            throw $e;
        } finally {
            fclose($file);
        }
    }

    /**
     * Fetch URL and write to the stream.
     */
    protected function transfer(string $url, $stream)
    {
        curl_setopt_array($this->curl, [
            CURLOPT_URL => $url,
            CURLOPT_FILE => $stream,
        ]);

        try {
            $response = curl_exec($this->curl);

            if ($response === false) {
                throw new TransferException(curl_error($this->curl));
            }
        } finally {
            $this->close();
        }
    }

    /**
     * Guess filename from the given URL.
     */
    protected function guessFilename(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        return pathinfo($path, PATHINFO_BASENAME) ?: $this->generateRandomFilename();
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
