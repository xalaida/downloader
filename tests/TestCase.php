<?php

namespace Nevadskiy\Downloader\Tests;

use Nevadskiy\Downloader\Tests\Constraint\DirectoryIsEmpty;
use PHPUnit\Framework\TestCase as BaseTestCase;

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
    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = $this->prepareStorageDirectory();

        require_once __DIR__.'/helpers.php';
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
     * Get a base URL of the server with fixture files.
     */
    protected function url(string $path = '/'): string
    {
        $url = $_ENV['TESTING_SERVER_URL'] ?? 'http://localhost:4020';

        return $url.'/'.ltrim($path, '/');
    }

    /**
     * Execute the given callback with the specified working directory.
     */
    protected function withWorkingDirectory(string $workDir, callable $callback)
    {
        $original = getcwd();

        chdir($workDir);

        $result = $callback();

        chdir($original);

        return $result;
    }

    /**
     * Asserts that a directory is empty.
     */
    public static function assertDirectoryIsEmpty(string $directory, string $message = ''): void
    {
        static::assertDirectoryExists($directory);

        static::assertThat($directory, new DirectoryIsEmpty(), $message);
    }
}
