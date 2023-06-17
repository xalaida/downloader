<?php

namespace Nevadskiy\Downloader;

interface RandomFilenameGenerator
{
    /**
     * Generate a random filename.
     */
    public function generate(): string;
}
