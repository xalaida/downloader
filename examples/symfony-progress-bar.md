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

        $this->setUpCurlDownloader();
    }

    /**
     * Set up the cURL downloader instance.
     */
    protected function setUpCurlDownloader()
    {
        $this->downloader->onProgress(function (int $total, int $loaded) {
            if ($total) {
                $this->progress->setMaxSteps($total);
            }

            if ($loaded) {
                $this->progress->setProgress($loaded);
            }
        });
    }

    /**
     * @inheritdoc
     */
    public function download(string $url, string $destination = null)
    {
        $this->progress = $this->output->createProgressBar();

        $this->progress->start();

        $this->downloader->download($url, $destination);

        $this->progress->finish();
    }
}
```

It is a simple decorator class for the base `Downloader` instance.

Use it in the console command class:

```php
$downloader = new ConsoleProgressDownloader(new CurlDownloader(), $this->getOutput());

$downloader->download('https://example.com/files/books.zip', __DIR__.'/storage/books.zip');
```
