<?php

namespace Nevadskiy\Downloader\Tests\Integration;

use Nevadskiy\Downloader\TempFile;
use Nevadskiy\Downloader\Tests\TestCase;
use RuntimeException;

class TempFileTest extends TestCase
{
    /** @test */
    public function it_can_be_deleted()
    {
        $file = new TempFile();

        $path = $file->getPath();

        self::assertFileExists($path);

        $file->delete();

        self::assertFileNotExists($path);
    }

    /** @test */
    public function it_can_be_automatically_deleted()
    {
        $file = new TempFile();

        $path = $file->getPath();

        self::assertFileExists($path);

        unset($file);

        self::assertFileNotExists($path);
    }

    /** @test */
    public function it_cannot_be_deleted_multiple_times()
    {
        $file = new TempFile();

        $path = $file->getPath();

        self::assertFileExists($path);

        $file->delete();

        self::assertFileNotExists($path);

        $this->expectException(RuntimeException::class);

        $file->delete();
    }

    /** @test */
    public function it_uses_temp_directory_by_default()
    {
        $file = new TempFile();

        self::assertFileExists($file->getPath());
        self::assertEquals(sys_get_temp_dir(), dirname($file->getPath()));

        $file->delete();
    }

    /** @test */
    public function it_can_be_created_in_custom_directory()
    {
        $storage = $this->prepareStorageDirectory();

        $file = new TempFile($storage);

        self::assertFileExists($file->getPath());
        self::assertEquals($storage, dirname($file->getPath()));

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
            self::assertDirectoryNotExists($directory);
        }
    }

    /** @test */
    public function it_can_be_written()
    {
        $file = new TempFile();

        $file->write('Hello, world!');

        self::assertStringEqualsFile($file->getPath(), 'Hello, world!');

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

        self::assertStringEqualsFile($tempPath, 'Hello, world!');
        self::assertFileNotExists($path);

        $file->save($path);

        self::assertStringEqualsFile($path, 'Hello, world!');
        self::assertFileNotExists($tempPath);
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
            self::assertFileNotExists($storage.'/hello-world-2.txt');
            self::assertStringEqualsFile($storage.'/hello-world.txt', 'Hello, world!');
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
            self::assertFileNotExists($path);
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

        self::assertStringEqualsFile($path, 'Hello, world!');
    }

    /** @test */
    public function it_cannot_be_deleted_when_file_is_missing()
    {
        $file = new TempFile();

        $path = $file->getPath();

        self::assertFileExists($path);

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
