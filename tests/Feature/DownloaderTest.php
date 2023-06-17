<?php

namespace Nevadskiy\Downloader\Tests\Feature;

use DateTime;
use InvalidArgumentException;
use Nevadskiy\Downloader\CurlDownloader;
use Nevadskiy\Downloader\Exceptions\DirectoryMissingException;
use Nevadskiy\Downloader\Exceptions\FileExistsException;
use Nevadskiy\Downloader\Exceptions\TransferException;
use Nevadskiy\Downloader\Tests\TestCase;

class DownloaderTest extends TestCase
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

    /** @test */
    public function it_downloads_page_by_url()
    {
        $destination = (new CurlDownloader())
            ->download($this->serverUrl(), $this->storage.'/home.txt');

        static::assertSame($this->storage.'/home.txt', $destination);
        static::assertFileExists($destination);
        static::assertStringEqualsFile($destination, 'Welcome home!');
    }

    /** @test */
    public function it_throws_exception_for_url_that_returns_http_error()
    {
        try {
            (new CurlDownloader())->download(
                $this->serverUrl('/fixtures/wrong-file.txt'),
                $this->storage.'/missing-file.txt'
            );

            static::fail('Expected NetworkException was not thrown');
        } catch (TransferException $e) {
            static::assertDirectoryIsEmpty($this->storage);
        }
    }

    /** @test */
    public function it_throws_exception_for_invalid_url()
    {
        $destination = $this->storage.'/invalid-url.txt';

        try {
            (new CurlDownloader())->download('invalid-url', $destination);

            static::fail('Expected NetworkException was not thrown');
        } catch (TransferException $e) {
            self::assertSame('Could not resolve host: invalid-url', $e->getMessage());
            static::assertFileNotExists($destination);
        }
    }

    /** @test */
    public function it_handles_destination_to_not_existing_directory()
    {
        try {
            (new CurlDownloader())->download(
                $this->serverUrl('/fixtures/hello-world.txt'),
                $this->storage.'/files/hello-world.txt'
            );

            static::fail('Expected DirectoryMissingException was not thrown');
        } catch (DirectoryMissingException $e) {
            static::assertSame($this->storage.'/files', $e->getPath());
        }
    }

    /** @test */
    public function it_handles_destination_to_existing_directory()
    {
        mkdir($this->storage.'/files', 0755);

        $destination = (new CurlDownloader())
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $this->storage.'/files');

        static::assertSame($this->storage.'/files/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_can_create_destination_directory_when_it_is_missing()
    {
        $destination = (new CurlDownloader())
            ->allowDirectoryCreation()
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $this->storage.'/files/hello-world.txt');

        static::assertSame($this->storage.'/files/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_can_create_destination_directory_recursively_when_it_is_missing()
    {
        $destination = (new CurlDownloader())
            ->allowRecursiveDirectoryCreation()
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $this->storage.'/files/2022/07/26/hello-world.txt');

        static::assertSame($this->storage.'/files/2022/07/26/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_downloads_file_by_destination_that_is_directory()
    {
        $destination = (new CurlDownloader())
            ->allowRecursiveDirectoryCreation()
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $this->storage.'/files/2022/07/26/');

        static::assertSame($this->storage.'/files/2022/07/26/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_can_specify_destination_directory_with_dot_syntax()
    {
        $destination = (new CurlDownloader())
            ->allowRecursiveDirectoryCreation()
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $this->storage.'/files/2022/07/26/.');

        static::assertSame($this->storage.'/files/2022/07/26/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_downloads_file_according_to_current_working_directory()
    {
        $destination = (new CurlDownloader())
            ->download($this->serverUrl('/fixtures/hello-world.txt'), './tests/storage');

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_downloads_file_to_base_directory_with_default_destination()
    {
        $destination = (new CurlDownloader())
            ->allowRecursiveDirectoryCreation()
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $this->storage);

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_throws_exception_when_file_already_exists()
    {
        $destination = $this->storage.'/hello-world.txt';

        file_put_contents($destination, 'Old content!');

        try {
            (new CurlDownloader())
                ->failIfExists()
                ->download($this->serverUrl('/fixtures/hello-world.txt'), $destination);

            static::fail('Expected FileExistsException was not thrown');
        } catch (FileExistsException $e) {
            static::assertStringEqualsFile($destination, 'Old content!');
        }
    }

    /** @test */
    public function it_can_return_already_existing_file()
    {
        $destination = $this->storage.'/hello-world.txt';

        file_put_contents($destination, 'Old content!');

        $destination = (new CurlDownloader())
            ->skipIfExists()
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $destination);

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertStringEqualsFile($destination, 'Old content!');
    }

    /** @test */
    public function it_can_update_content_when_file_already_exists_and_has_older_modification_date()
    {
        $destination = $this->storage.'/hello-world.txt';

        file_put_contents($destination, 'Old content!');

        touch($destination, DateTime::createFromFormat('m/d/Y', '1/10/2014')->getTimestamp());

        $destination = (new CurlDownloader())
            ->updateIfExists()
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $destination);

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_does_not_update_content_when_file_already_exists_and_has_newer_modification_date()
    {
        $destination = $this->storage.'/hello-world.txt';

        file_put_contents($destination, 'Old content!');

        $destination = (new CurlDownloader())
            ->updateIfExists()
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $destination);

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertStringEqualsFile($destination, 'Old content!');
    }

    /** @test */
    public function it_can_replace_content_when_file_already_exists()
    {
        $destination = $this->storage.'/hello-world.txt';

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
        $destination = (new CurlDownloader())->download($this->serverUrl('/fixtures/hello-world.txt'), $this->storage);

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_can_download_files_following_redirects_by_url()
    {
        $destination = (new CurlDownloader())
            ->followRedirects()
            ->download($this->serverUrl('/redirect/hello-world.txt'), $this->storage.'/hello-world.txt');

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_allows_to_specify_callbacks_on_curl_handle_instance()
    {
        $url = $this->serverUrl('/redirect/hello-world.txt');

        $destination = (new CurlDownloader())
            ->followRedirects()
            ->withCurlHandle(function ($ch) use ($url) {
                static::assertSame($url, curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
            })
            ->download($url, $this->storage.'/hello-world.txt');

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_allows_to_specify_curl_options()
    {
        $destination = (new CurlDownloader())
            ->followRedirects()
            ->withCurlOption(CURLOPT_HTTPHEADER, [
                sprintf('Authorization: Basic %s', base64_encode('client:secret')),
            ])
            ->download($this->serverUrl('/private/hello-world.txt'), $this->storage.'/hello-world.txt');

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_allows_to_specify_headers()
    {
        $destination = (new CurlDownloader())
            ->followRedirects()
            ->withHeaders([
                'Authorization' => sprintf('Basic %s', base64_encode('client:secret')),
            ])
            ->download($this->serverUrl('/private/hello-world.txt'), $this->storage.'/hello-world.txt');

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }

    /** @test */
    public function it_allows_to_specify_progress_hook()
    {
        $loaded = 0;

        $destination = (new CurlDownloader())
            ->onProgress(function (int $t, int $l) use (&$loaded) {
                $loaded = $l;
            })
            ->download($this->serverUrl('/fixtures/hello-world.txt'), $this->storage.'/hello-world.txt');

        static::assertSame(13, $loaded);

        static::assertSame($this->storage.'/hello-world.txt', $destination);
        static::assertFileExists($destination);
        static::assertFileEquals(__DIR__.'/../server/fixtures/hello-world.txt', $destination);
    }
}
