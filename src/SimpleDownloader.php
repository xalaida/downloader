<?php

namespace Nevadskiy\Downloader;

use Nevadskiy\Downloader\Exceptions\TransferException;
use Throwable;

class SimpleDownloader
{
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
        }

        $temp = tempnam($dir, 'tmp');

        $this->newFile($temp, function ($file) use ($url) {
            $this->transferToFile($url, $file);
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
    protected function transferToFile(string $url, $stream)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_FILE => $stream,
            CURLOPT_FAILONERROR => true,
        ]);

        try {
            $response = curl_exec($curl);

            if ($response === false) {
                throw new TransferException(curl_error($curl));
            }
        } finally {
            curl_close($curl);
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
}
