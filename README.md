# Downloader

[![Stand With Ukraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner-direct-single.svg)](https://stand-with-ukraine.pp.ua)

[![Latest Stable Version](https://poser.pugx.org/nevadskiy/downloader/v)](https://packagist.org/packages/nevadskiy/downloader)
[![Tests](https://github.com/nevadskiy/downloader/workflows/tests/badge.svg)](https://packagist.org/packages/nevadskiy/downloader)
[![Code Coverage](https://codecov.io/gh/nevadskiy/downloader/branch/master/graphs/badge.svg?branch=master)](https://packagist.org/packages/nevadskiy/downloader)
[![License](https://poser.pugx.org/nevadskiy/downloader/license)](https://packagist.org/packages/nevadskiy/downloader)

Download files using PHP and Curl.

## ✅ Requirements

- PHP 7.0 or newer

## 🔨 Usage

#### Downloading file by URL to the specified path

```php
use Nevadskiy\Downloader\CurlDownloader;

$downloader = new CurlDownloader();
$downloader->download('https://example.com/files/books.zip', __DIR__.'/storage/books.zip');
```

#### Integrating with the Symfony Console Progress Bar

Add a `ConsoleProgressDownloader` decorator class:

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

Use it in the console command class:

```php
$downloader = new ConsoleProgressDownloader(new CurlDownloader(), $this->getOutput());
$downloader->download('https://example.com/files/books.zip', __DIR__.'/storage/books.zip');
```

## ☕ Contributing

Thank you for considering contributing. Please see [CONTRIBUTING](CONTRIBUTING.md) for more information.

## 📜 License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
