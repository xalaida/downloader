<?php

namespace Nevadskiy\Downloader\Tests;

use Nevadskiy\Downloader\Tests\Constraint\DirectoryIsEmpty;
use Nevadskiy\Downloader\Tests\Support\TestingDirectory;
use PHPUnit\Framework\TestCase as BaseTestCase;
use const DIRECTORY_SEPARATOR;

class TestCase extends BaseTestCase
{
    /**
     * The storage dir.
     *
     * @var string
     */
    protected $storage;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->storage = $this->prepareStorageDirectory();

        require_once __DIR__.'/helpers.php';
    }

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

        (new TestingDirectory())->prepare($storage);

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
