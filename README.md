# Downloader

[![Stand With Ukraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner-direct-single.svg)](https://stand-with-ukraine.pp.ua)

[![Latest Stable Version](https://poser.pugx.org/nevadskiy/downloader/v)](https://packagist.org/packages/nevadskiy/downloader)
[![Tests](https://img.shields.io/github/workflow/status/nevadskiy/downloader/Tests?label=tests)](https://packagist.org/packages/nevadskiy/downloader)
[![Code Coverage](https://codecov.io/gh/nevadskiy/downloader/branch/master/graphs/badge.svg?branch=master)](https://packagist.org/packages/nevadskiy/downloader)
[![License](https://poser.pugx.org/nevadskiy/downloader/license)](https://packagist.org/packages/nevadskiy/downloader)

Download files using PHP and Curl.

## ✅ Requirements

- PHP 7.0 or newer

## 🔌 Installation

Install the package via composer.

```bash
composer require nevadskiy/downloader
````

## 🔨 Usage

Downloading a file by URL to the specified path:

```php
use Nevadskiy\Downloader\CurlDownloader;

$downloader = new CurlDownloader();
$downloader->download('https://example.com/files/books.zip', __DIR__.'/storage/books.zip');
```

Also see [usage example](examples/symfony-progress-bar.md) with the [Symfony Progress Bar](https://symfony.com/doc/current/components/console/helpers/progressbar.html) component.

## ☕ Contributing

Thank you for considering contributing. Please see [CONTRIBUTING](CONTRIBUTING.md) for more information.

## 📜 License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
