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

        $destination = $storage.'/hello-world.txt';

        $downloader = new CurlDownloader();

        $downloader->download($this->serverUrl().'/fixtures/hello-world.txt', $destination);

        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_downloads_page_by_url()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = $storage.'/home.txt';

        $downloader = new CurlDownloader();

        $downloader->download($this->serverUrl().'/', $destination);

        static::assertFileExists($destination);
        static::assertStringEqualsFile($destination, 'Welcome home!');
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

        $destination = $storage.'/invalid-url.txt';

        $downloader = new CurlDownloader();

        try {
            $downloader->download('invalid-url', $destination);

            static::fail('Expected RuntimeException was not thrown');
        } catch (InvalidArgumentException $e) {
            static::assertFileNotExists($destination);
        }
    }

    /** @test */
    public function it_handles_destination_to_not_existing_directory()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = $storage.'/files/hello-world.txt';

        $this->expectException(RuntimeException::class);

        $downloader = new CurlDownloader();

        $downloader->download($this->serverUrl().'/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_throws_exception_when_file_already_exists()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = $storage.'/hello-world.txt';

        file_put_contents($destination, 'Old content!');

        $downloader = new CurlDownloader();

        try {
            $downloader->download($this->serverUrl().'/fixtures/hello-world.txt', $destination);

            static::fail('Expected RuntimeException was not thrown');
        } catch (RuntimeException $e) {
            static::assertStringEqualsFile($destination, 'Old content!');
        }
    }

    /** @test */
    public function it_can_overwrite_a_file_content()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = $storage.'/hello-world.txt';

        file_put_contents($destination, 'Old content!');

        $downloader = new CurlDownloader();

        $downloader->overwrite();

        $downloader->download($this->serverUrl().'/fixtures/hello-world.txt', $destination);

        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_can_use_directory_as_destination_and_determine_file_name_from_url()
    {
        $storage = $this->prepareStorageDirectory();

        $downloader = new CurlDownloader();

        $destination = $downloader->download($this->serverUrl().'/fixtures/hello-world.txt', $storage);

        static::assertEquals($storage.'/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_can_download_files_following_redirects_by_url()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = $storage.'/hello-world.txt';

        $downloader = new CurlDownloader();

        $downloader->download($this->serverUrl().'/redirect/hello-world.txt', $destination);

        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_allows_to_specify_callbacks_on_curl_handle_instance()
    {
        $storage = $this->prepareStorageDirectory();

        $url = $this->serverUrl().'/redirect/hello-world.txt';
        $destination = $storage.'/hello-world.txt';

        $downloader = new CurlDownloader();

        $downloader->withCurlHandle(function ($ch) use ($url) {
            static::assertSame($url, curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
        });

        $downloader->download($url, $destination);

        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_allows_to_specify_curl_options()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = $storage.'/hello-world.txt';

        $downloader = new CurlDownloader();

        $downloader->withCurlOption(CURLOPT_HTTPHEADER, [
            sprintf('Authorization: Basic %s', base64_encode('client:secret')),
        ]);

        $downloader->download($this->serverUrl().'/private/hello-world.txt', $destination);

        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_allows_to_specify_headers()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = $storage.'/hello-world.txt';

        $downloader = new CurlDownloader();

        $downloader->withHeaders([
            'Authorization' => sprintf('Basic %s', base64_encode('client:secret')),
        ]);

        $downloader->download($this->serverUrl().'/private/hello-world.txt', $destination);

        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }
}
