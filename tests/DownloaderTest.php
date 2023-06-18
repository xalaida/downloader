<?php

namespace Nevadskiy\Downloader\Tests;

use Nevadskiy\Downloader\CurlDownloader;
use Nevadskiy\Downloader\Exceptions\DownloaderException;

class DownloaderTest extends TestCase
{
    /** @test */
    public function it_downloads_response_by_url()
    {
        (new CurlDownloader())
            ->download($this->url(), $destination = $this->storage.'/home.txt');

        static::assertSame($this->storage.'/home.txt', $destination);
        static::assertStringEqualsFile($destination, 'Welcome home!');
    }

    /** @test */
    public function it_downloads_file_by_url()
    {
        (new CurlDownloader())
            ->download($this->url('/hello-world.txt'), $destination = $this->storage.'/hello-world.txt');

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileEquals(__DIR__.'/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_downloads_multiple_files()
    {
        $downloader = new CurlDownloader();

        $downloader->download($this->url('/hello-world.txt'), $this->storage.'/hello-world.txt');
        $downloader->download($this->url('/hello-php.txt'), $this->storage.'/hello-php.txt');

        static::assertFileExists($this->storage.'/hello-world.txt');
        static::assertFileEquals(__DIR__.'/fixtures/hello-world.txt', $this->storage.'/hello-world.txt');

        static::assertFileExists($this->storage.'/hello-php.txt');
        static::assertFileEquals(__DIR__.'/fixtures/hello-php.txt', $this->storage.'/hello-php.txt');
    }

    /** @test */
    public function it_downloads_file_with_following_redirects()
    {
        $path = (new CurlDownloader())
            ->download($this->url('/redirect/hello-world.txt'), $this->storage.'/hello-world-redirect.txt');

        static::assertSame($this->storage.'/hello-world-redirect.txt', $path);
        static::assertFileExists($path);
        static::assertFileEquals(__DIR__.'/fixtures/hello-world.txt', $path);
    }

    /** @test */
    public function it_downloads_file_using_authorization_header()
    {
        $destination = (new CurlDownloader())
            ->withHeaders([
                sprintf('Authorization: Basic %s', base64_encode('client:secret')),
            ])
            ->download($this->url('/private/hello-world.txt'), $this->storage.'/hello-world.txt');

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileEquals(__DIR__.'/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_downloads_file_with_progress()
    {
        $bytes = 0;

        $destination = (new CurlDownloader())
            ->withProgress(function (int $download, int $downloaded, int $upload, int $uploaded) use (&$bytes) {
                $bytes = $downloaded;
            })
            ->download($this->url('/hello-world.txt'), $this->storage.'/hello-world.txt');

        static::assertSame(13, $bytes);

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileEquals(__DIR__.'/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_throws_exception_when_http_error_occurs()
    {
        try {
            (new CurlDownloader())
                ->download($this->url('/wrong-file.txt'), $this->storage.'/missing-file.txt');

            static::fail(sprintf('Expected [%s] was not thrown.', DownloaderException::class));
        } catch (DownloaderException $e) {
            static::assertDirectoryIsEmpty($this->storage);
        }
    }

    /** @test */
    public function it_throws_exception_when_invalid_url()
    {
        try {
            (new CurlDownloader())->download('invalid-url', $this->storage.'/invalid-url.txt');

            static::fail(sprintf('Expected [%s] was not thrown.', DownloaderException::class));
        } catch (DownloaderException $e) {
            self::assertSame('Could not resolve host: invalid-url', $e->getMessage());
            static::assertDirectoryIsEmpty($this->storage);
        }
    }
}
