<?php

namespace Nevadskiy\Downloader\Tests\Integration;

use Nevadskiy\Downloader\TempFile;
use Nevadskiy\Downloader\Tests\TestCase;
use RuntimeException;
use function dirname;

class TempFileTest extends TestCase
{
    /** @test */
    public function it_can_be_deleted()
    {
        $file = new TempFile();

        $path = $file->getPath();

        static::assertFileExists($path);

        $file->delete();

        static::assertFileNotExists($path);
    }

    /** @test */
    public function it_can_be_automatically_deleted()
    {
        $file = new TempFile();

        $path = $file->getPath();

        static::assertFileExists($path);

        unset($file);

        static::assertFileNotExists($path);
    }

    /** @test */
    public function it_cannot_be_deleted_multiple_times()
    {
        $file = new TempFile();

        $path = $file->getPath();

        static::assertFileExists($path);

        $file->delete();

        static::assertFileNotExists($path);

        $this->expectException(RuntimeException::class);

        $file->delete();
    }

    /** @test */
    public function it_uses_temp_directory_by_default()
    {
        $file = new TempFile();

        static::assertFileExists($file->getPath());
        static::assertSame(sys_get_temp_dir(), dirname($file->getPath()));

        $file->delete();
    }

    /** @test */
    public function it_can_be_created_in_custom_directory()
    {
        $storage = $this->prepareStorageDirectory();

        $file = new TempFile($storage);

        static::assertFileExists($file->getPath());
        static::assertSame($storage, dirname($file->getPath()));

        $file->delete();
    }

    /** @test */
    public function it_cannot_be_created_in_non_existent_directory()
    {
        $storage = $this->prepareStorageDirectory();

        $directory = $storage.'/files';

        try {
            new TempFile($directory);

            static::fail('Temp file created in non-existent directory');
        } catch (RuntimeException $e) {
            static::assertDirectoryNotExists($directory);
        }
    }

    /** @test */
    public function it_can_be_written()
    {
        $file = new TempFile();

        $file->write('Hello, world!');

        static::assertStringEqualsFile($file->getPath(), 'Hello, world!');

        $file->delete();
    }

    /** @test */
    public function it_can_be_saved_by_given_path()
    {
        $storage = $this->prepareStorageDirectory();

        $file = new TempFile();

        $file->write('Hello, world!');

        $path = $storage.'/hello-world.txt';
        $tempPath = $file->getPath();

        static::assertStringEqualsFile($tempPath, 'Hello, world!');
        static::assertFileNotExists($path);

        $file->save($path);

        static::assertStringEqualsFile($path, 'Hello, world!');
        static::assertFileNotExists($tempPath);
    }

    /** @test */
    public function it_cannot_be_saved_multiple_times()
    {
        $storage = $this->prepareStorageDirectory();

        $file = new TempFile();

        $file->write('Hello, world!');

        $file->save($storage.'/hello-world.txt');

        try {
            $file->save($storage.'/hello-world-2.txt');

            static::fail('TempFile was saved multiple times');
        } catch (RuntimeException $e) {
            static::assertFileNotExists($storage.'/hello-world-2.txt');
            static::assertStringEqualsFile($storage.'/hello-world.txt', 'Hello, world!');
        }
    }

    /** @test */
    public function it_cannot_be_moved_to_non_existent_directory()
    {
        $storage = $this->prepareStorageDirectory();

        $path = $storage.'/files/hello-world.txt';

        $file = new TempFile();

        $file->write('Hello, world!');

        try {
            $file->save($path);

            static::fail('File was saved to non-existent directory');
        } catch (RuntimeException $e) {
            static::assertFileNotExists($path);
        }
    }

    /** @test */
    public function it_overrides_existing_files_on_save()
    {
        $storage = $this->prepareStorageDirectory();

        $path = $storage.'/hello-world.txt';

        file_put_contents($path, 'Old content!');

        $file = new TempFile();

        $file->write('Hello, world!');

        $file->save($path);

        static::assertStringEqualsFile($path, 'Hello, world!');
    }

    /** @test */
    public function it_cannot_be_deleted_when_file_is_missing()
    {
        $file = new TempFile();

        $path = $file->getPath();

        static::assertFileExists($path);

        unlink($path);

        $this->expectException(RuntimeException::class);

        $file->delete();
    }

    /** @test */
    public function it_cannot_be_saved_when_path_is_directory()
    {
        $storage = $this->prepareStorageDirectory();

        $file = new TempFile();

        $file->write('Hello, world!');

        $this->expectException(RuntimeException::class);

        $file->save($storage);
    }
}
