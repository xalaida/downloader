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
     *
     * @throws TransferException
     * @throws RuntimeException
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

        $finalUrl = $this->newFile($temp, function ($file) use ($url) {
            return $this->transfer($url, $file);
        });

        $path = $path ?: $dir . DIRECTORY_SEPARATOR . $this->guessFilename($finalUrl);

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
    protected function transfer(string $url, $file): string
    {
        curl_setopt_array($this->curl, [
            CURLOPT_URL => $url,
            CURLOPT_FILE => $file,
        ]);

        try {
            $response = curl_exec($this->curl);

            if ($response === false) {
                throw new TransferException(curl_error($this->curl));
            }

            return curl_getinfo($this->curl, CURLINFO_EFFECTIVE_URL);
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
