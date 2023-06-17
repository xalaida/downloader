<?php

namespace Nevadskiy\Downloader;

use Nevadskiy\Downloader\Exceptions\TransferException;
use Throwable;

class SimpleDownloader
{
    /**
     * Download a file from the URL and save to the given path.
     */
    public function download(string $url, string $path)
    {
        $this->newFile($path, function ($file) use ($url) {
            $this->transferToFile($url, $file);
        });
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
}
