<?php

namespace Nevadskiy\Downloader\Filename;

class TempFilenameGenerator implements FilenameGenerator
{
    /**
     * @inheritdoc
     */
    public function generate(): string
    {
        return uniqid(mt_rand(), true);
    }
}
