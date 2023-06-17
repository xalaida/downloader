<?php

namespace Nevadskiy\Downloader\Tests\Feature;

use Nevadskiy\Downloader\Exceptions\TransferException;
use Nevadskiy\Downloader\SimpleDownloader;
use Nevadskiy\Downloader\Tests\TestCase;
use RuntimeException;

/**
 * @todo split into multiple tests.
 */
class SimpleDownloaderTest extends TestCase
{
    /** @test */
    public function it_downloads_response_by_url()
    {
        (new SimpleDownloader())
            ->download($this->serverUrl(), $destination = $this->storage.'/home.txt');

        static::assertSame($this->storage.'/home.txt', $destination);
        static::assertFileExists($destination);
        static::assertStringEqualsFile($destination, 'Welcome home!');
    }

    /** @test */
    public function it_downloads_file_by_url()
    {
        (new SimpleDownloader())
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $destination = $this->storage.'/hello-world.txt');

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_downloads_multiple_files()
    {
        $downloader = new SimpleDownloader();

        $downloader->download($this->serverUrl('/fixtures/hello-world.txt'), $this->storage.'/hello-world.txt');
        $downloader->download($this->serverUrl('/fixtures/hello-php.txt'), $this->storage.'/hello-php.txt');

        static::assertFileExists($this->storage.'/hello-world.txt');
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $this->storage.'/hello-world.txt');

        static::assertFileExists($this->storage.'/hello-php.txt');
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-php.txt', $this->storage.'/hello-php.txt');
    }

    /** @test */
    public function it_throws_exception_when_http_error_occurs()
    {
        try {
            (new SimpleDownloader())
                ->download($this->serverUrl('/fixtures/wrong-file.txt'), $this->storage.'/missing-file.txt');

            static::fail('Expected DownloaderException was not thrown');
        } catch (TransferException $e) {
            static::assertDirectoryIsEmpty($this->storage);
        }
    }

    /** @test */
    public function it_throws_exception_when_invalid_url()
    {
        try {
            (new SimpleDownloader())->download('invalid-url', $this->storage.'/invalid-url.txt');

            static::fail('Expected NetworkException was not thrown');
        } catch (TransferException $e) {
            self::assertSame('Could not resolve host: invalid-url', $e->getMessage());
            static::assertDirectoryIsEmpty($this->storage);
        }
    }

    /** @test */
    public function it_handles_destination_to_missing_directory()
    {
        try {
            (new SimpleDownloader())
                ->download($this->serverUrl('/fixtures/hello-world.txt'), $this->storage.'/files/hello-world.txt');

            static::fail('Expected RuntimeException was not thrown');
        } catch (RuntimeException $e) {
            self::assertSame(sprintf('Directory [%s] is missing.', $this->storage.'/files'), $e->getMessage());
        }
    }

    // it_can_create_destination_directory_when_it_is_missing

    // it_can_create_destination_directory_recursively_when_it_is_missing

    // it_downloads_file_by_destination_that_is_directory

    // it_can_specify_destination_directory_with_dot_syntax

    // it_downloads_file_according_to_current_working_directory

    // change_working_directory_for_downloader

    /** @test */
    public function it_downloads_files_with_following_redirects()
    {
        $path = (new SimpleDownloader())
            ->download($this->serverUrl('/redirect/hello-world.txt'), $this->storage.'/hello-world-redirect.txt');

        static::assertSame($this->storage.'/hello-world-redirect.txt', $path);
        static::assertFileExists($path);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $path);
    }

    // @todo when no content - throw exception + delete temp file
}
