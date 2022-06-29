<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\App;

require '../../../../vendor/autoload.php';

$app = new App;

$app->get('/', function (Request $request, Response $response, array $args) {
    $response->getBody()->write("Hello");

    return $response;
});

$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");

    return $response;
});

$app->run();
