<?php

namespace Nevadskiy\Downloader;

class SimpleDownloader
{
    public function download(string $url, string $path)
    {
        $file = fopen($path, 'wb');

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_FILE => $file,
        ]);

        curl_exec($curl);

        curl_close($curl);

        fclose($file);
    }
}
