<?php

namespace Nevadskiy\Downloader\Tests\Feature;

use DateTime;
use InvalidArgumentException;
use Nevadskiy\Downloader\CurlDownloader;
use Nevadskiy\Downloader\Exceptions\DirectoryMissingException;
use Nevadskiy\Downloader\Exceptions\FileExistsException;
use Nevadskiy\Downloader\Exceptions\TransferException;
use Nevadskiy\Downloader\SimpleDownloader;
use Nevadskiy\Downloader\Tests\TestCase;

class SimpleDownloaderTest extends TestCase
{
    // @todo detect filename from mime type
    // @todo detect filename from redirected URL

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->storage = $this->prepareStorageDirectory();
    }

    /** @test */
    public function it_downloads_files_by_url()
    {
        (new SimpleDownloader())
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $destination = $this->storage.'/hello-world.txt');

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_downloads_page_by_url()
    {
        (new SimpleDownloader())
            ->download($this->serverUrl(), $destination = $this->storage.'/home.txt');

        static::assertSame($this->storage.'/home.txt', $destination);
        static::assertFileExists($destination);
        static::assertStringEqualsFile($destination, 'Welcome home!');
    }

    /** @test */
    public function it_11throws_exception_for_url_that_returns_http_error()
    {
        try {
            (new SimpleDownloader())->download(
                $this->serverUrl('/fixtures/wrong-file.txt'),
                $this->storage.'/missing-file.txt'
            );

            static::fail('Expected DownloaderException was not thrown');
        } catch (TransferException $e) {
            static::assertDirectoryIsEmpty($this->storage);
        }
    }
}
