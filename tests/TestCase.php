<?php

namespace Nevadskiy\Downloader\Tests;

use Nevadskiy\Downloader\Tests\Constraint\DirectoryIsEmpty;
use Nevadskiy\Downloader\Tests\Uses\TestingDirectory;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    use TestingDirectory;

    /**
     * Get a base URL of the server with fixture files.
     */
    protected function serverUrl(string $path = '/'): string
    {
        $url = $_ENV['TESTING_SERVER_URL'] ?? 'http://127.0.0.1:8888';

        return $url . DIRECTORY_SEPARATOR . ltrim($path, '/');
    }

    /**
     * Prepare a testing storage directory.
     */
    protected function prepareStorageDirectory(): string
    {
        $storage = __DIR__.'/storage';

        $this->prepareDirectory($storage);

        return $storage;
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
