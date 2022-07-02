<?php

namespace Nevadskiy\Downloader\Tests\Integration;

use InvalidArgumentException;
use Nevadskiy\Downloader\CurlDownloader;
use Nevadskiy\Downloader\Tests\TestCase;
use RuntimeException;
use Symfony\Component\Process\Process;

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
    public function it_downloads_files_by_url()
    {
        $storage = $this->prepareStorageDirectory();

        $path = $storage.'/hello-world.txt';

        $downloader = new CurlDownloader();
        $downloader->download('http://localhost:8888/fixtures/hello-world.txt', $path);

        static::assertFileExists($path);
        static::assertFileEquals(__DIR__.'/../fixtures/hello-world.txt', $path);
    }

    /** @test */
    public function it_downloads_page_by_url()
    {
        $storage = $this->prepareStorageDirectory();

        $path = $storage.'/hello-world.txt';

        // TODO: try to run server directly from here.

        $downloader = new CurlDownloader();
        $downloader->download('http://localhost:8888/', $path);

        static::assertFileExists($path);
        static::assertStringEqualsFile($path, 'Hello world!');
    }

    /** @test */
    public function it_throws_exception_for_wrong_url_that_returns_http_error()
    {
        $storage = $this->prepareStorageDirectory();

        $path = $storage.'/missing-file.txt';

        $downloader = new CurlDownloader();

        try {
            $downloader->download('http://localhost:8888/fixtures/wrong-file.txt', $path);

            $this->fail('Expected RuntimeException was not thrown');
        } catch (RuntimeException $e) {
            static::assertFileNotExists($path);
        }
    }

    /** @test */
    public function it_throws_exception_for_invalid_url()
    {
        $storage = $this->prepareStorageDirectory();

        $path = $storage.'/invalid-url.txt';

        $downloader = new CurlDownloader();

        try {
            $downloader->download('invalid-url', $path);

            $this->fail('Expected RuntimeException was not thrown');
        } catch (InvalidArgumentException $e) {
            static::assertFileNotExists($path);
        }
    }

    /** @test */
    public function it_handles_path_to_not_existing_directory()
    {
        $storage = $this->prepareStorageDirectory();

        $path = $storage.'/files/hello-world.txt';

        $this->expectException(RuntimeException::class);

        $downloader = new CurlDownloader();
        $downloader->download('http://localhost:8888/fixtures/hello-world.txt', $path);
    }

    /** @test */
    public function it_throws_exception_when_file_already_exists()
    {
        $storage = $this->prepareStorageDirectory();

        $path = $storage.'/hello-world.txt';

        file_put_contents($path, 'Old content!');

        $downloader = new CurlDownloader();

        try {
            $downloader->download('http://localhost:8888/fixtures/hello-world.txt', $path);

            $this->fail('Expected RuntimeException was not thrown');
        } catch (RuntimeException $e) {
            static::assertStringEqualsFile($path, 'Old content!');
        }
    }

    /** @test */
    public function it_can_overwrite_a_file_content()
    {
        $storage = $this->prepareStorageDirectory();

        $path = $storage.'/hello-world.txt';

        file_put_contents($path, 'Old content!');

        $downloader = new CurlDownloader();

        $downloader->overwrite();

        $downloader->download('http://localhost:8888/fixtures/hello-world.txt', $path);

        static::assertFileExists($path);
        static::assertFileEquals(__DIR__.'/../fixtures/hello-world.txt', $path);
    }
}
