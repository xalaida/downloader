<?php

namespace Nevadskiy\Downloader\Tests;

use Nevadskiy\Downloader\CurlDownloader;

class DestinationTest extends TestCase
{
    /**
     * @test
     */
    public function it_downloads_file_to_directory_that_ends_with_separator(): void
    {
        $destination = (new CurlDownloader())
            ->download($this->url('/hello-world.txt'), $this->storage.'/');

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileEquals(__DIR__.'/fixtures/hello-world.txt', $destination);
    }

    /**
     * @test
     */
    public function it_downloads_file_to_directory_that_ends_with_dot(): void
    {
        $destination = (new CurlDownloader())
            ->download($this->url('/hello-world.txt'), $this->storage.'/.');

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileEquals(__DIR__.'/fixtures/hello-world.txt', $destination);
    }

    /**
     * @test
     */
    public function it_downloads_file_to_relative_destination_directory(): void
    {
        $destination = (new CurlDownloader())
            ->download($this->url('/hello-world.txt'), 'tests/storage');

        static::assertSame('tests/storage/hello-world.txt', $destination);
        static::assertFileEquals(__DIR__.'/fixtures/hello-world.txt', $destination);
    }

    /**
     * @test
     */
    public function it_downloads_file_to_relative_destination_directory_that_starts_with_dot(): void
    {
        $destination = (new CurlDownloader())
            ->download($this->url('/hello-world.txt'), './tests/storage');

        static::assertSame('./tests/storage/hello-world.txt', $destination);
        static::assertFileEquals(__DIR__.'/fixtures/hello-world.txt', $destination);
    }

    /**
     * @test
     */
    public function it_downloads_file_to_relative_destination_path(): void
    {
        $destination = (new CurlDownloader())
            ->download($this->url('/hello-world.txt'), 'tests/storage/hello-world-1.txt');

        static::assertSame('tests/storage/hello-world-1.txt', $destination);
        static::assertFileEquals(__DIR__.'/fixtures/hello-world.txt', $destination);
    }
}
