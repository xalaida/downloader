<?php

namespace Nevadskiy\Downloader\Tests\Feature;

use DateTime;
use InvalidArgumentException;
use Nevadskiy\Downloader\CurlDownloader;
use Nevadskiy\Downloader\Exceptions\DirectoryMissingException;
use Nevadskiy\Downloader\Exceptions\NetworkException;
use Nevadskiy\Downloader\Tests\TestCase;
use RuntimeException;

class DownloaderTest extends TestCase
{
    /** @test */
    public function it_downloads_files_by_url()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = (new CurlDownloader())
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $storage.'/hello-world.txt');

        static::assertEquals($storage.'/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_downloads_page_by_url()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = (new CurlDownloader())
            ->download($this->serverUrl(), $storage.'/home.txt');

        static::assertEquals($storage.'/home.txt', $destination);
        static::assertFileExists($destination);
        static::assertStringEqualsFile($destination, 'Welcome home!');
    }

    /** @test */
    public function it_throws_exception_for_wrong_url_that_returns_http_error()
    {
        $storage = $this->prepareStorageDirectory();

        try {
            (new CurlDownloader())->download(
                $this->serverUrl('/fixtures/wrong-file.txt'),
                $storage.'/missing-file.txt'
            );

            static::fail('Expected NetworkException was not thrown');
        } catch (NetworkException $e) {
            static::assertDirectoryIsEmpty($storage);
        }
    }

    /** @test */
    public function it_throws_exception_for_invalid_url()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = $storage.'/invalid-url.txt';

        try {
            (new CurlDownloader())->download('invalid-url', $destination);

            static::fail('Expected RuntimeException was not thrown');
        } catch (InvalidArgumentException $e) {
            static::assertFileNotExists($destination);
        }
    }

    /** @test */
    public function it_handles_destination_to_not_existing_directory()
    {
        $storage = $this->prepareStorageDirectory();

        $this->expectException(DirectoryMissingException::class);

        (new CurlDownloader())->download(
            $this->serverUrl('/fixtures/hello-world.txt'),
            $storage.'/files/hello-world.txt'
        );
    }

    /** @test */
    public function it_handles_destination_to_existing_directory()
    {
        $storage = $this->prepareStorageDirectory();

        mkdir($storage.'/files', 0755);

        $destination = (new CurlDownloader())
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $storage.'/files');

        static::assertEquals($storage.'/files/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_can_create_destination_directory_when_it_is_missing()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = (new CurlDownloader())
            ->allowDirectoryCreation()
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $storage.'/files/hello-world.txt');

        static::assertEquals($storage.'/files/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_can_create_destination_directory_recursively_when_it_is_missing()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = (new CurlDownloader())
            ->allowRecursiveDirectoryCreation()
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $storage.'/files/2022/07/26/hello-world.txt');

        static::assertEquals($storage.'/files/2022/07/26/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_downloads_file_by_destination_that_is_directory()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = (new CurlDownloader())
            ->allowRecursiveDirectoryCreation()
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $storage.'/files/2022/07/26/');

        static::assertEquals($storage.'/files/2022/07/26/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_can_specify_base_directory()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = (new CurlDownloader())
            ->allowRecursiveDirectoryCreation()
            ->baseDirectory($storage)
            ->download($this->serverUrl('/fixtures/hello-world.txt'), 'files/hello-world.txt');

        static::assertEquals($storage.'/files/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_can_specify_destination_directory_with_dot_syntax()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = (new CurlDownloader())
            ->allowRecursiveDirectoryCreation()
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $storage.'/files/2022/07/26/.');

        static::assertEquals($storage.'/files/2022/07/26/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_downloads_file_according_to_current_working_directory()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = (new CurlDownloader())
            ->download($this->serverUrl('/fixtures/hello-world.txt'), './tests/storage');

        static::assertEquals($storage.'/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_downloads_file_to_base_directory_with_default_destination()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = (new CurlDownloader())
            ->allowRecursiveDirectoryCreation()
            ->baseDirectory($storage)
            ->download($this->serverUrl('/fixtures/hello-world.txt'));

        static::assertEquals($storage.'/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_throws_exception_when_file_already_exists()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = $storage.'/hello-world.txt';

        file_put_contents($destination, 'Old content!');

        try {
            (new CurlDownloader())->download($this->serverUrl('/fixtures/hello-world.txt'), $destination);

            static::fail('Expected RuntimeException was not thrown');
        } catch (RuntimeException $e) {
            static::assertStringEqualsFile($destination, 'Old content!');
        }
    }

    /** @test */
    public function it_can_return_already_existing_file()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = $storage.'/hello-world.txt';

        file_put_contents($destination, 'Old content!');

        $destination = (new CurlDownloader())
            ->skipIfExists()
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $destination);

        static::assertEquals($storage.'/hello-world.txt', $destination);
        static::assertStringEqualsFile($destination, 'Old content!');
    }

    /** @test */
    public function it_can_update_content_when_file_already_exists_and_has_older_modification_date()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = $storage.'/hello-world.txt';

        file_put_contents($destination, 'Old content!');

        touch($destination, DateTime::createFromFormat('m/d/Y', '1/10/2014')->getTimestamp());

        $destination = (new CurlDownloader())
            ->updateIfExists()
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $destination);

        static::assertEquals($storage.'/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_does_not_update_content_when_file_already_exists_and_has_newer_modification_date()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = $storage.'/hello-world.txt';

        file_put_contents($destination, 'Old content!');

        $destination = (new CurlDownloader())
            ->updateIfExists()
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $destination);

        static::assertEquals($storage.'/hello-world.txt', $destination);
        static::assertStringEqualsFile($destination, 'Old content!');
    }

    /** @test */
    public function it_can_replace_content_when_file_already_exists()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = $storage.'/hello-world.txt';

        file_put_contents($destination, 'Old content!');

        (new CurlDownloader())
            ->replaceIfExists()
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $destination);

        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_can_use_directory_as_destination_and_determine_file_name_from_url()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = (new CurlDownloader())->download($this->serverUrl('/fixtures/hello-world.txt'), $storage);

        static::assertEquals($storage.'/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_can_download_files_following_redirects_by_url()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = (new CurlDownloader())->download(
            $this->serverUrl('/redirect/hello-world.txt'),
            $storage.'/hello-world.txt'
        );

        static::assertEquals($storage.'/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_allows_to_specify_callbacks_on_curl_handle_instance()
    {
        $storage = $this->prepareStorageDirectory();

        $url = $this->serverUrl('/redirect/hello-world.txt');

        $destination = (new CurlDownloader())
            ->withCurlHandle(function ($ch) use ($url) {
                static::assertSame($url, curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
            })
            ->download($url, $storage.'/hello-world.txt');

        static::assertEquals($storage.'/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_allows_to_specify_curl_options()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = (new CurlDownloader())
            ->withCurlOption(CURLOPT_HTTPHEADER, [
                sprintf('Authorization: Basic %s', base64_encode('client:secret')),
            ])
            ->download($this->serverUrl('/private/hello-world.txt'), $storage.'/hello-world.txt');

        static::assertEquals($storage.'/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_allows_to_specify_headers()
    {
        $storage = $this->prepareStorageDirectory();

        $destination = (new CurlDownloader())
            ->withHeaders([
                'Authorization' => sprintf('Basic %s', base64_encode('client:secret')),
            ])
            ->download($this->serverUrl('/private/hello-world.txt'), $storage.'/hello-world.txt');

        static::assertEquals($storage.'/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_allows_to_specify_progress_hook()
    {
        $storage = $this->prepareStorageDirectory();

        $loaded = 0;

        $destination = (new CurlDownloader())
            ->onProgress(function (int $t, int $l) use (&$loaded) {
                $loaded = $l;
            })
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $storage.'/hello-world.txt');

        self::assertEquals(13, $loaded);

        static::assertEquals($storage.'/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }
}
