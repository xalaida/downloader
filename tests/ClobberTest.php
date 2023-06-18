<?php

namespace Nevadskiy\Downloader\Tests;

use DateTime;
use Nevadskiy\Downloader\CurlDownloader;
use Nevadskiy\Downloader\Exceptions\FilenameMissingException;
use Nevadskiy\Downloader\Exceptions\FileExistsException;
use Nevadskiy\Downloader\FilenameGenerator\FilenameGenerator;

class ClobberTest extends TestCase
{
    /**
     * @test
     */
    public function it_throws_exception_when_file_already_exists(): void
    {
        $destination = $this->storage.'/hello-world.txt';

        file_put_contents($destination, 'Old content!');

        $tempGenerator = $this->createMock(FilenameGenerator::class);

        $tempGenerator->expects(static::once())
            ->method('generate')
            ->willReturn('TEMPFILE');

        try {
            (new CurlDownloader())
                ->failIfExists()
                ->setTempFilenameGenerator($tempGenerator)
                ->download($this->url('/hello-world.txt'), $destination);

            static::fail(sprintf('Expected [%s] was not thrown.', FileExistsException::class));
        } catch (FileExistsException $e) {
            static::assertStringEqualsFile($destination, 'Old content!');
            static::assertFileNotExists($this->storage.'/TEMPFILE');
            static::assertSame($destination, $e->getPath());
        }
    }

    /**
     * @test
     */
    public function it_skips_downloading_when_file_already_exists(): void
    {
        $destination = $this->storage.'/hello-world.txt';

        file_put_contents($destination, 'Old content!');

        $destination = (new CurlDownloader())
            ->skipIfExists()
            ->download($this->url('/hello-world.txt'), $destination);

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertStringEqualsFile($destination, 'Old content!');
    }

    /**
     * @test
     */
    public function it_replaces_content_of_existing_file(): void
    {
        $destination = $this->storage.'/hello-world.txt';

        file_put_contents($destination, 'Old content!');

        (new CurlDownloader())
            ->replaceIfExists()
            ->download($this->url('/hello-world.txt'), $destination);

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileEquals(__DIR__.'/fixtures/hello-world.txt', $destination);
    }

    /**
     * @test
     */
    public function it_updates_old_content_of_existing_file(): void
    {
        $destination = $this->storage.'/hello-world.txt';

        file_put_contents($destination, 'Old content!');

        touch($destination, DateTime::createFromFormat('m/d/Y', '1/10/2014')->getTimestamp());

        (new CurlDownloader())
            ->updateIfExists()
            ->download($this->url('/hello-world.txt'), $destination);

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileEquals(__DIR__.'/fixtures/hello-world.txt', $destination);
    }

    /**
     * @test
     */
    public function it_does_not_update_content_when_file_already_exists_and_was_not_modified_since(): void
    {
        $destination = $this->storage.'/hello-world.txt';

        file_put_contents($destination, 'Old content!');

        $destination = (new CurlDownloader())
            ->updateIfExists()
            ->download($this->url('/hello-world.txt'), $destination);

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertStringEqualsFile($destination, 'Old content!');
    }

    /**
     * @test
     */
    public function it_does_not_include_timestamp_when_file_is_missing(): void
    {
        (new CurlDownloader())
            ->updateIfExists()
            ->download($this->url('/hello-world.txt'), $destination = $this->storage.'/hello-world.txt');

        static::assertFileEquals(__DIR__.'/fixtures/hello-world.txt', $destination);
    }

    /**
     * @test
     */
    public function it_does_not_update_content_when_file_already_exists_and_has_newer_last_modified_timestamp(): void
    {
        $destination = $this->storage.'/hello-world.txt';

        file_put_contents($destination, 'Old content!');

        $destination = (new CurlDownloader())
            ->updateIfExists()
            ->download($this->url('/hello-world'), $destination);

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertStringEqualsFile($destination, 'Old content!');
    }

    /**
     * @test
     */
    public function it_throws_exception_if_destination_is_not_filepath_when_including_timestamps(): void
    {
        file_put_contents($this->storage.'/hello-world.txt', 'Old content!');

        try {
            (new CurlDownloader())
                ->updateIfExists()
                ->download($this->url('/hello-world.txt'), $this->storage);

            static::fail(sprintf('Expected [%s] was not thrown.', FilenameMissingException::class));
        } catch (FilenameMissingException $e) {
            static::assertStringEqualsFile($this->storage.'/hello-world.txt', 'Old content!');
        }
    }
}
