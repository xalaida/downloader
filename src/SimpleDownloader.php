<?php

namespace Nevadskiy\Downloader;

use Nevadskiy\Downloader\Exceptions\DownloaderException;

class SimpleDownloader
{
    public function download(string $url, string $path)
    {
        $file = fopen($path, 'wb');

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_FILE => $file,
            CURLOPT_FAILONERROR => true,
        ]);

        try {
            $response = curl_exec($curl);

            if ($response === false) {
                throw new DownloaderException(curl_error($curl));
            }
        } finally {
            curl_close($curl);

            fclose($file);

            unlink($path);
        }
    }
}
