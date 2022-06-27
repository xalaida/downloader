<?php

namespace Nevadskiy\Downloader;

interface Downloader
{
    /**
     * Download a file by the URL to the given directory.
     */
    public function download(string $url, string $directory, string $name = null);
}
