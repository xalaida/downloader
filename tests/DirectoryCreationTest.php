<?php

namespace Nevadskiy\Downloader\Tests;

use Nevadskiy\Downloader\CurlDownloader;
use Nevadskiy\Downloader\Exceptions\DirectoryMissingException;

class DirectoryCreationTest extends TestCase
{
    /**
     * @test
     */
    public function it_throws_exception_when_destination_directory_is_missing(): void
    {
        try {
            (new CurlDownloader())
                ->download($this->url('/hello-world.txt'), $this->storage.'/files/hello-world.txt');

            static::fail(sprintf('Expected [%s] was not thrown.', DirectoryMissingException::class));
        } catch (DirectoryMissingException $e) {
            self::assertSame($this->storage.'/files', $e->getPath());
        }
    }

    /**
     * @test
     */
    public function it_creates_destination_directory_when_it_is_missing(): void
    {
        $destination = (new CurlDownloader())
            ->allowDirectoryCreation()
            ->download($this->url('/hello-world.txt'), $this->storage.'/files/hello-world.txt');

        static::assertSame($this->storage.'/files/hello-world.txt', $destination);
        static::assertFileEquals(__DIR__.'/fixtures/hello-world.txt', $destination);
    }

    /**
     * @test
     */
    public function it_creates_destination_directory_recursively_when_it_is_missing(): void
    {
        $destination = (new CurlDownloader())
            ->allowRecursiveDirectoryCreation()
            ->download($this->url('/hello-world.txt'), $this->storage.'/files/2022/07/26/hello-world.txt');

        static::assertSame($this->storage.'/files/2022/07/26/hello-world.txt', $destination);
        static::assertFileEquals(__DIR__.'/fixtures/hello-world.txt', $destination);
    }

    /**
     * @test
     */
    public function it_assumes_last_path_segment_is_filename_when_destination_is_missing_directory(): void
    {
        $destination = (new CurlDownloader())
            ->allowRecursiveDirectoryCreation()
            ->download($this->url('/hello-world.txt'), $this->storage.'/files/2022/07/26');

        static::assertSame($this->storage.'/files/2022/07/26', $destination);
        static::assertFileEquals(__DIR__.'/fixtures/hello-world.txt', $destination);
    }

    /**
     * @test
     */
    public function it_assumes_last_path_segment_is_directory_when_destination_ends_with_separator(): void
    {
        $destination = (new CurlDownloader())
            ->allowRecursiveDirectoryCreation()
            ->download($this->url('/hello-world.txt'), $this->storage.'/files/2022/07/26/');

        static::assertSame($this->storage.'/files/2022/07/26/hello-world.txt', $destination);
        static::assertFileEquals(__DIR__.'/fixtures/hello-world.txt', $destination);
    }

    /**
     * @test
     */
    public function it_assumes_last_path_segment_is_directory_when_destination_ends_with_separator_dot(): void
    {
        $destination = (new CurlDownloader())
            ->allowRecursiveDirectoryCreation()
            ->download($this->url('/hello-world.txt'), $this->storage.'/files/2022/07/26/.');

        static::assertSame($this->storage.'/files/2022/07/26/hello-world.txt', $destination);
        static::assertFileEquals(__DIR__.'/fixtures/hello-world.txt', $destination);
    }

    /**
     * @test
     */
    public function it_assumes_last_path_segment_is_filename_when_destination_ends_with_dot(): void
    {
        $destination = (new CurlDownloader())
            ->allowRecursiveDirectoryCreation()
            ->download($this->url('/hello-world.txt'), $this->storage.'/files/2022/07/26.');

        static::assertSame($this->storage.'/files/2022/07/26.', $destination);
        static::assertFileEquals(__DIR__.'/fixtures/hello-world.txt', $destination);
    }
}
