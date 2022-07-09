<?php

namespace Nevadskiy\Downloader\Tests;

use FilesystemIterator;
use Nevadskiy\Downloader\Tests\Constraint\DirectoryIsEmpty;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function prepareStorageDirectory(): string
    {
        $storage = __DIR__.'/storage';

        $this->prepareDirectory($storage);

        return $storage;
    }

    protected function prepareDirectory(string $directory)
    {
        if (is_dir($directory)) {
            $this->cleanDirectory($directory);
        } else {
            mkdir($directory, 0755, true);
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

    /**
     * Asserts that a directory is empty.
     */
    public static function assertDirectoryIsEmpty(string $directory, string $message = '')
    {
        static::assertDirectoryExists($directory);

        static::assertThat($directory, new DirectoryIsEmpty(), $message);
    }
}
