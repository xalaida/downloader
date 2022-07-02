# downloader

Download files using PHP and Curl

/**
* @TODO
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
class CurlDownloader implements Downloader
{
    public function download(string $url, string $path)
    {
        $directory = is_dir($path) ? $path : dirname($path);

        $tempFile = tempnam($directory, 'tmp_');

        $stream = @fopen($tempFile, 'wb+');

        $headers = [];

        $this->collectHeaders($ch, $headers);

        rename($tempFile, $path);
    }

    private function getFileName(array $headers, string $url): string
    {
        if (isset($headers['content-disposition'])) {
            return $this->getFileNameFromContentDisposition($headers['content-disposition']);
        }

        throw new RuntimeException('Cannot determine a file name');
    }

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
