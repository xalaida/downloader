<?php

namespace Nevadskiy\Downloader\Tests\Uses;

use FilesystemIterator;

trait TestingDirectory
{
    /**
     * Clean up the directory or create it if it is missing.
     */
    protected function prepareDirectory(string $directory)
    {
        if (is_dir($directory)) {
            $this->cleanDirectory($directory);
        } else {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Clean up the given directory.
     */
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
