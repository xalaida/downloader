<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\App;
use Slim\Http\Stream;

require '../../../../vendor/autoload.php';

$app = new App;

$app->get('/', function (Request $request, Response $response, array $args) {
    $path = __DIR__.'/../../../fixtures/hello-world.txt';

    return $response->withBody(new Stream(fopen($path, 'rb')))
        ->withHeader('Content-Disposition', 'attachment; filename=hello-world.txt;')
        ->withHeader('Content-Type', mime_content_type($path))
        ->withHeader('Content-Length', filesize($path));
});

$app->run();
