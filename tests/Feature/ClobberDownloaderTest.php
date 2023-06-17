<?php

namespace Nevadskiy\Downloader\Tests\Feature;

use Nevadskiy\Downloader\Exceptions\FileExistsException;
use Nevadskiy\Downloader\Filename\FilenameGenerator;
use Nevadskiy\Downloader\SimpleDownloader;
use Nevadskiy\Downloader\Tests\TestCase;

class ClobberDownloaderTest extends TestCase
{
    /** @test */
    public function it_throws_exception_when_file_already_exists()
    {
        $destination = $this->storage.'/hello-world.txt';

        file_put_contents($destination, 'Old content!');

        $tempGenerator = $this->createMock(FilenameGenerator::class);

        $tempGenerator->expects(static::once())
            ->method('generate')
            ->willReturn('TEMPFILE');

        try {
            (new SimpleDownloader())
                ->setTempFilenameGenerator($tempGenerator)
                ->download($this->serverUrl('/fixtures/hello-world.txt'), $destination);

            static::fail('Expected FileExistsException was not thrown');
        } catch (FileExistsException $e) {
            static::assertStringEqualsFile($destination, 'Old content!');
            static::assertFileNotExists($this->storage.'/TEMPFILE');
        }
    }

    /** @test */
    public function it_skips_dowloading_when_file_already_exists()
    {
        $destination = $this->storage.'/hello-world.txt';

        file_put_contents($destination, 'Old content!');

        $destination = (new SimpleDownloader())
            ->skipIfExists()
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $destination);

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertStringEqualsFile($destination, 'Old content!');
    }
}
