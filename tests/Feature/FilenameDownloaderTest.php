<?php

namespace Nevadskiy\Downloader\Tests\Feature;

use Nevadskiy\Downloader\Filename\FilenameGenerator;
use Nevadskiy\Downloader\SimpleDownloader;
use Nevadskiy\Downloader\Tests\TestCase;

class FilenameDownloaderTest extends TestCase
{
    /** @test */
    public function it_generates_filename_from_url_when_destination_is_directory()
    {
        $path = (new SimpleDownloader())
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $this->storage);

        static::assertSame($path, $this->storage.'/hello-world.txt');
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $path);
    }

    /** @test */
    public function it_generates_filename_from_url_after_redirects()
    {
        $path = (new SimpleDownloader())
            ->download($this->serverUrl('/redirect'), $this->storage);

        static::assertSame($path, $this->storage.'/hello-world.txt');
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $path);
    }

    /** @test */
    public function it_generates_filename_from_url_and_mime_type_when_destination_is_directory()
    {
        $path = (new SimpleDownloader())
            ->download($this->serverUrl('/hello-world'), $this->storage);

        static::assertSame($path, $this->storage.'/hello-world.txt');
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $path);
    }

    /** @test */
    public function it_generates_filename_from_content_disposition_header_when_destination_is_directory()
    {
        $path = (new SimpleDownloader())
            ->download($this->serverUrl('/content'), $this->storage);

        static::assertSame($path, $this->storage.'/hello-world.txt');
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $path);
    }

    /** @test */
    public function it_generates_random_filename_when_destination_is_directory()
    {
        $filenameGenerator = $this->createMock(FilenameGenerator::class);

        $filenameGenerator->expects(static::once())
            ->method('generate')
            ->willReturn('RANDOMFILE');

        $path = (new SimpleDownloader())
            ->setRandomFilenameGenerator($filenameGenerator)
            ->download($this->serverUrl(), $this->storage);

        static::assertEquals($this->storage.'/RANDOMFILE', $path);
        static::assertStringEqualsFile($path, 'Welcome home!');
    }
}
