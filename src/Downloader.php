<?php

namespace Nevadskiy\Downloader;

interface Downloader
{
    /**
     * Download a file by the URL to the given path.
     *
     * @return void
     */
    public function download(string $url, string $path);
}
