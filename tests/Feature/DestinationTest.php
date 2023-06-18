<?php

namespace Nevadskiy\Downloader\Tests\Feature;

use Nevadskiy\Downloader\SimpleDownloader;
use Nevadskiy\Downloader\Tests\TestCase;

class DestinationTest extends TestCase
{
    /** @test */
    public function it_downloads_file_to_directory_that_ends_with_separator()
    {
        $destination = (new SimpleDownloader())
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $this->storage.'/');

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_downloads_file_to_directory_that_ends_with_dot()
    {
        $destination = (new SimpleDownloader())
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $this->storage.'/.');

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_downloads_file_to_relative_destination_directory()
    {
        $destination = (new SimpleDownloader())
            ->download($this->serverUrl('/fixtures/hello-world.txt'), 'tests/storage');

        static::assertSame('tests/storage/hello-world.txt', $destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_downloads_file_to_relative_destination_directory_that_starts_with_dot()
    {
        $destination = (new SimpleDownloader())
            ->download($this->serverUrl('/fixtures/hello-world.txt'), './tests/storage');

        static::assertSame('./tests/storage/hello-world.txt', $destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_downloads_file_to_relative_destination_path()
    {
        $destination = (new SimpleDownloader())
            ->download($this->serverUrl('/fixtures/hello-world.txt'), 'tests/storage/hello-world-1.txt');

        static::assertSame('tests/storage/hello-world-1.txt', $destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }
}
