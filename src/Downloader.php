<?php

namespace Nevadskiy\Downloader;

interface Downloader
{
    /**
     * Download a file by the URL to the given destination and return its path.
     */
    public function download(string $url, string $destination): string;
}
