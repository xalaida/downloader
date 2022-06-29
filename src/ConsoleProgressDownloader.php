<?php

namespace Nevadskiy\Downloader;

use Symfony\Component\Console\Style\OutputStyle;

class ConsoleProgressDownloader implements Downloader
{
    /**
     * The cURL downloader instance.
     *
     * @var CurlDownloader
     */
    protected $downloader;

    /**
     * The symfony output instance.
     *
     * @var OutputStyle
     */
    protected $output;

    /**
     * Make a new downloader instance.
     */
    public function __construct(CurlDownloader $downloader, OutputStyle $output)
    {
        $this->downloader = $downloader;
        $this->output = $output;

        $this->setUpCurl();
    }

    /**
     * Set up the cURL handle instance.
     *
     * @return void
     */
    protected function setUpCurl()
    {
        $this->downloader->withCurlHandle(function ($ch) {
            $progress = $this->output->createProgressBar();

            $progress->start();

            curl_setopt($ch, CURLOPT_NOPROGRESS, false);

            curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($ch, $downloadBytes, $downloadedBytes) use ($progress) {
                if ($downloadBytes) {
                    $progress->setMaxSteps($downloadBytes);
                }

                if ($downloadedBytes) {
                    $progress->setProgress($downloadedBytes);
                }
            });

            $progress->finish();
        });
    }

    /**
     * @inheritdoc
     */
    public function download(string $url, string $path)
    {
        $this->downloader->download($url, $path);
    }
}
