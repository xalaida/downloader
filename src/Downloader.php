<?php

namespace Nevadskiy\Downloader;

interface Downloader
{
    /**
     * Download a file by the URL to the given destination.
     */
    public function download(string $url, string $destination);
}
