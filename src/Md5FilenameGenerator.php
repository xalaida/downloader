<?php

namespace Nevadskiy\Downloader;

class Md5FilenameGenerator implements RandomFilenameGenerator
{
    /**
     * @inheritdoc
     */
    public function generate(): string
    {
        return md5(uniqid(mt_rand(), true));
    }
}
