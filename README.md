# downloader
Download files using PHP and Curl

/**
* @TODO
* - [ ] check large files (download using chunk)
* - [ ] add possibility to follow redirects
* - [ ] check url to file to stream (without content length)
* - [ ] add possibility to download or specify headers to access url (authorization, POST method, etc)
* - [ ] provide unzip downloader
* - [ ] consider writing 'url' driver to league flysystem
* - [ ] add github actions to test using windows filesystem
*
* @TODO
* refactor with set up traits: https://dev.to/adamquaile/using-traits-to-organise-phpunit-tests-39g3
* add possibility to run server directly from test (`pcntl_fork` api might be useful)
  */


```
<?php

namespace Nevadskiy\Downloader;

use InvalidArgumentException;
use RuntimeException;

/**
 * @TODO add helper methods for most common cURL options.
 */
class CurlDownloader implements Downloader
{
    /**
     * The cURL options array.
     *
     * @var array
     */
    protected $curlOptions;

    /**
     * The cURL handle callbacks.
     *
     * @var array
     */
    protected $curlHandleCallbacks = [];

    /**
     * Indicates if the downloader should overwrite the content if a file already exists.
     *
     * @var bool
     */
    protected $overwrite = false;

    /**
     * Make a new downloader instance.
     */
    public function __construct(array $curlOptions = [])
    {
        $this->curlOptions = $this->curlOptions() + $curlOptions;
    }

    /**
     * The default cURL options.
     * @TODO consider filtering reserved options that can break curl downloading configuration.
     */
    protected function curlOptions(): array
    {
        return [
            // CURLINFO_HEADER_OUT => true,
            CURLOPT_FAILONERROR => true,
        ];
    }

    /**
     * Add a cURL option with the given value.
     *
     * @return void
     */
    public function withCurlOption($option, $value)
    {
        $this->curlOptions[$option] = $value;
    }

    /**
     * Add a cURL handle callback.
     *
     * @return void
     */
    public function withCurlHandle(callable $callback)
    {
        $this->curlHandleCallbacks[] = $callback;
    }

    /**
     * Overwrite the content if a file already exists.
     */
    public function overwrite(bool $overwrite = true)
    {
        $this->overwrite = $overwrite;
    }

    /**
     * @inheritdoc
     */
    public function download(string $url, string $path)
    {
        $this->ensureUrlIsValid($url);

        $directory = is_dir($path) ? $path : dirname($path);

        $tempFile = tempnam($directory, 'tmp_');

//        if (! $this->overwrite && file_exists($path)) {
//            throw new RuntimeException(sprintf('A file "%s" already exists', $path));
//        }

        $stream = @fopen($tempFile, 'wb+');

        if (! $stream) {
            throw new RuntimeException(sprintf('Cannot open file %s', $tempFile));
        }

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_FILE, $stream);

        foreach ($this->curlOptions as $option => $value) {
            curl_setopt($ch, $option, $value);
        }

        foreach ($this->curlHandleCallbacks as $callback) {
            $callback($ch);
        }

        $headers = [];

        $this->collectHeaders($ch, $headers);

        $response = curl_exec($ch);

        $error = $this->captureError($ch, $response);

        curl_close($ch);

        fclose($stream);

        if ($error) {
            unlink($tempFile);

            throw new RuntimeException($error);
        } else {
            if (is_dir($path)) {
                $path = $directory . DIRECTORY_SEPARATOR . $this->getFileName($headers, $url);
            }

            // TODO: check if path is available (check how chrome behaves in that case).

            rename($tempFile, $path);
        }
    }

    private function getFileName(array $headers, string $url): string
    {
        if (isset($headers['content-disposition'])) {
            return $this->getFileNameFromContentDisposition($headers['content-disposition']);
        }

        throw new RuntimeException('Cannot determine a file name');
    }

    /**
     * Get a file name from the content disposition header.
     *
     * @param string $disposition
     * @return string|null
     */
    private function getFileNameFromContentDisposition(string $disposition)
    {
        foreach (explode(';', $disposition) as $part) {
            $params = explode('=', trim($part), 2);

            if (! in_array($params[0], ['filename', 'name'])) {
                continue;
            }

            if (! isset($params[1])) {
                continue;
            }

            return $params[1];
        }

        return null;
    }

//    /**
//     * Guess the file name by the given URL.
//     */
//    protected function guessFileName(string $url): string
//    {
//        $position = strrpos($url, '/');
//
//        if ($position === false) {
//            // TODO: provide default file name (probably check file mime-type).
//        }
//
//        return substr($url, $position + 1);
//    }

    /**
     * Capture error from the cURL response.
     *
     * @param resource $ch
     * @return string|null
     */
    protected function captureError($ch, bool $response)
    {
        if ($response) {
            return null;
        }

        return curl_error($ch);
    }

    /**
     * Ensure that the given URL is valid.
     *
     * @return void
     */
    protected function ensureUrlIsValid(string $url)
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('The given URL is invalid.');
        }
    }

    /**
     * Collect the response headers.
     *
     * @param resource $ch
     */
    protected function collectHeaders($ch, array &$headers)
    {
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, string $header) use (&$headers) {
            var_dump('here');
            var_dump($header);
            $headerLength = strlen($header);

            $header = explode(':', $header, 2);

            if (count($header) < 2) {
                return $headerLength;
            }

            $headers[strtolower(trim($header[0]))] = trim($header[1]);

            return $headerLength;
        });
    }
}

```
