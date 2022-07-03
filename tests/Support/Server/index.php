<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\App;
use Slim\Http\Stream;

// Fix URL path with dots "."
if (PHP_SAPI === 'cli-server') {
    $_SERVER['SCRIPT_NAME'] = pathinfo(__FILE__, PATHINFO_BASENAME);
}

require __DIR__.'/../../../vendor/autoload.php';

$app = new App;

$app->get('/', function (Request $request, Response $response, array $args) {
    return $response->getBody()->write('Hello world!');
});

$app->get('/fixtures/{fixture}', function (Request $request, Response $response, array $args) {
    $filename = $args['fixture'];
    $fixturesDirectory = __DIR__.'/../../fixtures';
    $fixture = $fixturesDirectory.'/'.$filename;

    return $response->withBody(new Stream(fopen($fixture, 'r')))
        ->withHeader('Content-Disposition', sprintf('attachment; filename=%s;', $filename))
        ->withHeader('Content-Type', mime_content_type($fixture))
        ->withHeader('Content-Length', filesize($fixture));
});

$app->get('/redirect/{fixture}', function (Request $request, Response $response, array $args) {
    return $response->withRedirect(sprintf('/fixtures/%s', $args['fixture']), 301);
});

$app->run();
