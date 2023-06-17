<?php

namespace Nevadskiy\Downloader\Tests\Feature;

use DateTime;
use InvalidArgumentException;
use Nevadskiy\Downloader\CurlDownloader;
use Nevadskiy\Downloader\Exceptions\DirectoryMissingException;
use Nevadskiy\Downloader\Exceptions\FileExistsException;
use Nevadskiy\Downloader\Exceptions\NetworkException;
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
        $destination = (new CurlDownloader())
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $this->storage.'/hello-world.txt');

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }
}
