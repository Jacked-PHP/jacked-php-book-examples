<?php

const ROOT_DIR = __DIR__;

use OpenSwoole\Http\Server;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;
use OpenSwoole\Server\Port;

$server = new Server("0.0.0.0", 80);

/** @var Port $sslPort */
$sslPort = $server->listen("0.0.0.0", 443, SWOOLE_SOCK_TCP | SWOOLE_SSL);

$sslPort->set([
    'ssl_cert_file' => __DIR__ . '/certs/local.swooleexample.com.pem',
    'ssl_key_file' => __DIR__ . '/certs/local.swooleexample.com-key.pem',
    'ssl_allow_self_signed' => false, // <- this can be useful for development environment
    'open_http_protocol' => true,
]);

$sslPort->on("request", function (Request $request, Response $response) use ($sslPort) {
    $response->end(sprintf("<h1>Server listening to port: %s.</h1>", $sslPort->port));
});

$server->on("start", function (Server $server) use ($sslPort) {
    echo sprintf('Swoole http server is started at http://%s:%s and http://%s:%s', $server->host, $server->port, $sslPort->host, $sslPort->port), PHP_EOL;
});

$server->on("request", function (Request $request, Response $response) use ($server) {
    $response->end(sprintf("<h1>Server listening to port: %s.</h1>", $server->port));
});

$server->start();
