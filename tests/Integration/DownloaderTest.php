<?php

namespace Nevadskiy\Downloader\Tests\Integration;

use InvalidArgumentException;
use Nevadskiy\Downloader\CurlDownloader;
use Nevadskiy\Downloader\DownloaderException;
use Nevadskiy\Downloader\Tests\TestCase;
use RuntimeException;

class DownloaderTest extends TestCase
{
    /** @test */
    public function it_downloads_files_by_url()
    {
        $storage = $this->prepareStorageDirectory();

        $path = $storage.'/hello-world.txt';

        $downloader = new CurlDownloader();
        $downloader->download($this->serverUrl().'/fixtures/hello-world.txt', $path);

        static::assertFileExists($path);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $path);
    }

    /** @test */
    public function it_downloads_page_by_url()
    {
        $storage = $this->prepareStorageDirectory();

        $path = $storage.'/home.txt';

        $downloader = new CurlDownloader();
        $downloader->download($this->serverUrl().'/', $path);

        static::assertFileExists($path);
        static::assertStringEqualsFile($path, 'Welcome home!');
    }

    /** @test */
    public function it_throws_exception_for_wrong_url_that_returns_http_error()
    {
        $storage = $this->prepareStorageDirectory();

        $downloader = new CurlDownloader();

        try {
            $downloader->download($this->serverUrl().'/fixtures/wrong-file.txt', $storage.'/missing-file.txt');

            static::fail('Expected DownloaderException was not thrown');
        } catch (DownloaderException $e) {
            static::assertDirectoryIsEmpty($storage);
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

            static::fail('Expected RuntimeException was not thrown');
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
        $downloader->download($this->serverUrl().'/fixtures/hello-world.txt', $path);
    }

    /** @test */
    public function it_throws_exception_when_file_already_exists()
    {
        $storage = $this->prepareStorageDirectory();

        $path = $storage.'/hello-world.txt';

        file_put_contents($path, 'Old content!');

        $downloader = new CurlDownloader();

        try {
            $downloader->download($this->serverUrl().'/fixtures/hello-world.txt', $path);

            static::fail('Expected RuntimeException was not thrown');
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

        $downloader->download($this->serverUrl().'/fixtures/hello-world.txt', $path);

        static::assertFileExists($path);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $path);
    }

    /** @test */
    public function it_can_use_directory_as_path_and_determine_file_name_from_url()
    {
        $storage = $this->prepareStorageDirectory();

        $downloader = new CurlDownloader();
        $downloader->download($this->serverUrl().'/fixtures/hello-world.txt', $storage);

        $path = $storage.'/hello-world.txt';

        static::assertFileExists($path);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $path);
    }

    /** @test */
    public function it_can_download_files_following_redirects_by_url()
    {
        $storage = $this->prepareStorageDirectory();

        $path = $storage.'/hello-world.txt';

        $downloader = new CurlDownloader();
        $downloader->download($this->serverUrl().'/redirect/hello-world.txt', $path);

        static::assertFileExists($path);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $path);
    }

    /** @test */
    public function it_allows_to_specify_callbacks_on_curl_handle_instance()
    {
        $storage = $this->prepareStorageDirectory();

        $url = $this->serverUrl().'/redirect/hello-world.txt';
        $path = $storage.'/hello-world.txt';

        $downloader = new CurlDownloader();

        $downloader->withCurlHandle(function ($ch) use ($url) {
            static::assertEquals($url, curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
        });

        $downloader->download($url, $path);

        static::assertFileExists($path);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $path);
    }

    /** @test */
    public function it_allows_to_specify_curl_options()
    {
        $storage = $this->prepareStorageDirectory();

        $path = $storage.'/hello-world.txt';

        $downloader = new CurlDownloader();

        $downloader->withCurlOption(CURLOPT_HTTPHEADER, [
            sprintf('Authorization: Basic %s', base64_encode('client:secret')),
        ]);

        $downloader->download($this->serverUrl().'/private/hello-world.txt', $path);

        static::assertFileExists($path);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $path);
    }

    /** @test */
    public function it_allows_to_specify_headers()
    {
        $storage = $this->prepareStorageDirectory();

        $path = $storage.'/hello-world.txt';

        $downloader = new CurlDownloader();

        $downloader->withHeaders([
            'Authorization' => sprintf('Basic %s', base64_encode('client:secret')),
        ]);

        $downloader->download($this->serverUrl().'/private/hello-world.txt', $path);

        static::assertFileExists($path);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $path);
    }
}
