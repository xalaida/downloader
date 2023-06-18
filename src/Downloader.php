<?php

namespace Nevadskiy\Downloader;

use Nevadskiy\Downloader\Exceptions\DownloaderException;

interface Downloader
{
    /**
     * Download a file from the URL and save to the given path.
     *
     * @throws DownloaderException
     */
    public function download(string $url, string $destination = null): string;
}
