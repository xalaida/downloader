<?php

namespace Nevadskiy\Downloader\FilenameGenerator;

interface FilenameGenerator
{
    /**
     * Generate a random filename.
     */
    public function generate(): string;
}
