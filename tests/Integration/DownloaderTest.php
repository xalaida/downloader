<?php

namespace Nevadskiy\Downloader\Tests\Integration;

use Nevadskiy\Downloader\Tests\TestCase;
use Symfony\Component\Process\Process;

/**
 * @TODO
 * - [ ] check invalid url
 * - [ ] check url simple html page (not file)
 * - [ ] check url to directory with files
 * - [ ] check url to file to stream (without content length)
 * - [ ] add possibility to download or specify headers to access url (authorization, POST method, etc)
 * - [ ] check filesystem path instead of url
 * - [ ] provide unzip downloader
 */
class DownloaderTest extends TestCase
{
    /**
     * @var Process
     */
    protected $process;

    protected function setUp()
    {
        parent::setUp();

        $this->process = new Process(vsprintf('php -S %s:%s -t %s', [
            'localhost',
            '8888',
            realpath(__DIR__.'/../Support/Server/File'),
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
        $response = $this->get('http://localhost:8888/');

        var_dump($response);

        die;
    }

    protected function get(string $url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }
}
