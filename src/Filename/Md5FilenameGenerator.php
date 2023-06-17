<?php

namespace Nevadskiy\Downloader\Filename;

class Md5FilenameGenerator implements FilenameGenerator
{
    /**
     * @inheritdoc
     */
    public function generate(): string
    {
        return md5(uniqid(mt_rand(), true));
    }
}
