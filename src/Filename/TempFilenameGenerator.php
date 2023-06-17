<?php

namespace Nevadskiy\Downloader\Filename;

class TempFilenameGenerator implements FilenameGenerator
{
    /**
     * @inheritdoc
     */
    public function generate(): string
    {
        return 'tmp'.uniqid(mt_rand(), true);
    }
}
