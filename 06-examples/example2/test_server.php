<?php

use OpenSwoole\Http\Server;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;

$server = new Server('0.0.0.0', 8080);

$server->on('start', function (Server $server) {
    echo 'Swoole http server is started at http://0.0.0.0:8080' . PHP_EOL;
});

$server->on('request', function (Request $request, Response $response) {
    $response->header('Content-Type', 'text/plain');
    $response->end('Hello World' . PHP_EOL);
});

$server->start();
