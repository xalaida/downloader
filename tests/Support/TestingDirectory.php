<?php

namespace Nevadskiy\Downloader\Tests\Support;

use FilesystemIterator;

class TestingDirectory
{
    /**
     * Clean up the directory or create it if it is missing.
     */
    public function prepare(string $directory)
    {
        if (is_dir($directory)) {
            $this->clean($directory);
        } else {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Clean up the given directory.
     */
    public function clean(string $directory, bool $preserve = true)
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = new FilesystemIterator($directory);

        foreach ($items as $item) {
            if ($item->isDir() && ! $item->isLink()) {
                $this->clean($item->getPathname(), false);
            } else {
                @unlink($item->getPathname());
            }
        }

        if (! $preserve) {
            @rmdir($directory);
        }
    }
}
