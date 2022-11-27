<?php

namespace Nevadskiy\Downloader\Tests\Unit;

use Nevadskiy\Downloader\CurlDownloader;
use Nevadskiy\Downloader\Tests\TestCase;
use Psr\Log\LoggerInterface;

class CurlDownloaderTest extends TestCase
{
    /** @test */
    public function it_uses_logger()
    {
        $storage = $this->prepareStorageDirectory();

        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects(static::exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Downloading file "{url}" to destination "{path}"'],
                ['File "{url}" downloaded to destination "{path}"']
            );

        $downloader = new CurlDownloader();

        $downloader->setLogger($logger);

        $destination = $downloader->download($this->serverUrl('/fixtures/hello-world.txt'), $storage);

        static::assertFileExists($destination);
    }
}
