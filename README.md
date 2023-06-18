[![Stand With Ukraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner-direct-single.svg)](https://stand-with-ukraine.pp.ua)

# Downloader

[![PHPUnit](https://img.shields.io/github/actions/workflow/status/nevadskiy/downloader/phpunit.yml?branch=master)](https://packagist.org/packages/nevadskiy/downloader)
[![Code Coverage](https://img.shields.io/codecov/c/github/nevadskiy/downloader)](https://packagist.org/packages/nevadskiy/downloader)
[![Latest Stable Version](https://img.shields.io/packagist/v/nevadskiy/downloader)](https://packagist.org/packages/nevadskiy/downloader)
[![License](https://img.shields.io/github/license/nevadskiy/downloader)](https://packagist.org/packages/nevadskiy/downloader)

⬇️ Download files using PHP and Curl.

## ✅ Requirements

- PHP 7.1 or newer

## 🔌 Installation

Install the package via Composer:

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

## ☕ Contributing

Thank you for considering contributing. Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for more information.

## 📜 License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

## 🔨 To Do List

- [ ] add logger
- [ ] continue downloading partially-downloaded files
- [ ] sync timestamps with a file on the server
- [ ] stats with download speed using curl `CURLINFO_SPEED_DOWNLOAD` option
- [ ] test on windows
- [ ] test different response codes
- [ ] phpstan
- [ ] increment filename if exists (hello-world.txt => hello-world.txt.1)
