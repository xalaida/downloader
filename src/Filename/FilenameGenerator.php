<?php

namespace Nevadskiy\Downloader\Filename;

interface FilenameGenerator
{
    /**
     * Generate a random filename.
     */
    public function generate(): string;
}
