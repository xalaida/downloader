<?php

namespace Nevadskiy\Downloader\Tests;

use FilesystemIterator;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function prepareDirectory(string $directory)
    {
        if (is_dir($directory)) {
            $this->cleanDirectory($directory);
        } else {
            mkdir($directory, 755, true);
        }
    }

    protected function cleanDirectory(string $directory, bool $preserve = true)
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = new FilesystemIterator($directory);

        foreach ($items as $item) {
            if ($item->isDir() && ! $item->isLink()) {
                $this->cleanDirectory($item->getPathname(), false);
            } else {
                @unlink($item->getPathname());
            }
        }

        if (! $preserve) {
            @rmdir($directory);
        }
    }
}
