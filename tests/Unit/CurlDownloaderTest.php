<?php

namespace Nevadskiy\Downloader\Tests\Unit;

use Nevadskiy\Downloader\CurlDownloader;
use Nevadskiy\Downloader\Tests\Fake\FakeLogger;
use Nevadskiy\Downloader\Tests\TestCase;

class CurlDownloaderTest extends TestCase
{
    /** @test */
    public function it_uses_logger()
    {
        $storage = $this->prepareStorageDirectory();

        $logger = new FakeLogger();

        $downloader = new CurlDownloader();

        $downloader->setLogger($logger);

        $destination = $downloader->download($this->serverUrl('/fixtures/hello-world.txt'), $storage);

        static::assertFileExists($destination);
        static::assertTrue($logger->hasMessage('Downloading file "{url}" to destination "{path}"', 'info'));
        static::assertTrue($logger->hasMessage('File "{url}" downloaded to destination "{path}"', 'info'));
    }
}
