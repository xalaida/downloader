<?php

namespace Nevadskiy\Downloader\Tests\Integration;

use Nevadskiy\Downloader\CurlDownloader;
use Nevadskiy\Downloader\Tests\TestCase;
use Symfony\Component\Process\Process;

/**
 * @TODO
 * - [ ] check invalid url
 * - [ ] test different name from Content-Disposition header
 * - [ ] create directory if missing to store file
 * - [ ] check url simple html page (not file)
 * - [ ] check url to directory with files
 * - [ ] check url to file to stream (without content length)
 * - [ ] add possibility to download or specify headers to access url (authorization, POST method, etc)
 * - [ ] check filesystem path instead of url
 * - [ ] provide unzip downloader
 *
 * @TODO
 * refactor with set up traits: https://dev.to/adamquaile/using-traits-to-organise-phpunit-tests-39g3
 */
class DownloaderTest extends TestCase
{
    protected $process;

    protected function setUp()
    {
        parent::setUp();

        $this->process = new Process(vsprintf('php -S %s:%s -t %s %s', [
            'localhost',
            '8888',
            realpath(__DIR__.'/../Support/Server/File'),
            'index.php'
        ]));

        $this->process->start();

        usleep(100000);
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->process->stop();
    }

    /** @test */
    public function it_download_files_from_url()
    {
        $storage = __DIR__.'/../storage';

        $this->prepareDirectory($storage);

        $path = $storage.'/hello-world.txt';

        $downloader = new CurlDownloader();
        $downloader->download('http://localhost:8888/fixtures/hello-world.txt', $path);

        self::assertFileExists($path);
        self::assertFileEquals(__DIR__.'/../fixtures/hello-world.txt', $path);
    }
}
