# Integrating with the Symfony Console Progress Bar

Add a `ConsoleProgressDownloader` class:

```php
<?php

namespace App;

use Symfony\Component\Console\Helper\ProgressBar;
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
     * The progress bar instance.
     *
     * @var ProgressBar
     */
    protected $progress;

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
     */
    protected function setUpCurl()
    {
        $this->downloader->withCurlOption(CURLOPT_NOPROGRESS, false);

        $this->downloader->withCurlOption(CURLOPT_PROGRESSFUNCTION, function ($ch, $downloadBytes, $downloadedBytes) {
            if ($downloadBytes) {
                $this->progress->setMaxSteps($downloadBytes);
            }

            if ($downloadedBytes) {
                $this->progress->setProgress($downloadedBytes);
            }
        });
    }

    /**
     * @inheritdoc
     */
    public function download(string $url, string $path)
    {
        $this->progress = $this->output->createProgressBar();

        $this->progress->start();

        $this->downloader->download($url, $path);

        $this->progress->finish();
    }
}
```

It is a simple decorator for base `Downloader` instance.

Use it in the console command class:

```php
$downloader = new ConsoleProgressDownloader(new CurlDownloader(), $this->getOutput());

$downloader->download('https://example.com/files/books.zip', __DIR__.'/storage/books.zip');
```
