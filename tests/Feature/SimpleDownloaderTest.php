<?php

namespace Nevadskiy\Downloader\Tests\Feature;

use Nevadskiy\Downloader\Exceptions\DirectoryMissingException;
use Nevadskiy\Downloader\Exceptions\DownloaderException;
use Nevadskiy\Downloader\SimpleDownloader;
use Nevadskiy\Downloader\Tests\TestCase;

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
        static::assertStringEqualsFile($destination, 'Welcome home!');
    }

    /** @test */
    public function it_downloads_file_by_url()
    {
        (new SimpleDownloader())
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $destination = $this->storage.'/hello-world.txt');

        static::assertSame($this->storage.'/hello-world.txt', $destination);
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
    public function it_downloads_file_with_following_redirects()
    {
        $path = (new SimpleDownloader())
            ->download($this->serverUrl('/redirect/hello-world.txt'), $this->storage.'/hello-world-redirect.txt');

        static::assertSame($this->storage.'/hello-world-redirect.txt', $path);
        static::assertFileExists($path);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $path);
    }

    /** @test */
    public function it_downloads_file_using_authorization_header()
    {
        $destination = (new SimpleDownloader())
            ->withHeaders([
                sprintf('Authorization: Basic %s', base64_encode('client:secret')),
            ])
            ->download($this->serverUrl('/private/hello-world.txt'), $this->storage.'/hello-world.txt');

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_downloads_file_with_progress()
    {
        $bytes = 0;

        $destination = (new SimpleDownloader())
            ->withProgress(function (int $download, int $downloaded, int $upload, int $uploaded) use (&$bytes) {
                $bytes = $downloaded;
            })
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $this->storage.'/hello-world.txt');

        static::assertSame(13, $bytes);

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_throws_exception_when_http_error_occurs()
    {
        try {
            (new SimpleDownloader())
                ->download($this->serverUrl('/fixtures/wrong-file.txt'), $this->storage.'/missing-file.txt');

            static::fail(sprintf('Expected [%s] was not thrown.', DownloaderException::class));
        } catch (DownloaderException $e) {
            static::assertDirectoryIsEmpty($this->storage);
        }
    }

    /** @test */
    public function it_throws_exception_when_invalid_url()
    {
        try {
            (new SimpleDownloader())->download('invalid-url', $this->storage.'/invalid-url.txt');

            static::fail(sprintf('Expected [%s] was not thrown.', DownloaderException::class));
        } catch (DownloaderException $e) {
            self::assertSame('Could not resolve host: invalid-url', $e->getMessage());
            static::assertDirectoryIsEmpty($this->storage);
        }
    }

    /** @test */
    public function it_throws_exception_when_destination_directory_is_missing()
    {
        try {
            (new SimpleDownloader())
                ->download($this->serverUrl('/fixtures/hello-world.txt'), $this->storage.'/files/hello-world.txt');

            static::fail(sprintf('Expected [%s] was not thrown.', DirectoryMissingException::class));
        } catch (DirectoryMissingException $e) {
            self::assertSame(sprintf('Directory [%s] does not exits.', $this->storage.'/files'), $e->getMessage());
        }
    }

    /** @test */
    public function it_creates_destination_directory_when_it_is_missing()
    {
        $destination = (new SimpleDownloader())
            ->allowDirectoryCreation()
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $this->storage.'/files/hello-world.txt');

        static::assertSame($this->storage.'/files/hello-world.txt', $destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_creates_destination_directory_recursively_when_it_is_missing()
    {
        $destination = (new SimpleDownloader())
            ->allowRecursiveDirectoryCreation()
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $this->storage.'/files/2022/07/26/hello-world.txt');

        static::assertSame($this->storage.'/files/2022/07/26/hello-world.txt', $destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_downloads_file_to_directory_with_separator_ending()
    {
        $destination = (new SimpleDownloader())
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $this->storage.'/');

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_downloads_file_to_directory_with_dot_ending()
    {
        $destination = (new SimpleDownloader())
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $this->storage.'/.');

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    // it_can_specify_destination_directory_with_dot_syntax

    // it_downloads_file_according_to_current_working_directory

    // change_working_directory_for_downloader

    // @todo test different response codes.

    // @todo when no content - throw exception + delete temp file.
}
