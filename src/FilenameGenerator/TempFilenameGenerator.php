<?php

namespace Nevadskiy\Downloader\FilenameGenerator;

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
