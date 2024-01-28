<?php

use OpenSwoole\Http\Response;
use OpenSwoole\Websocket\Server;
use OpenSwoole\Http\Request;
use OpenSwoole\WebSocket\Frame;

$server = new Server("0.0.0.0", 9501);

$server->on('start', function (Server $server) {
    echo 'OpenSwoole WebSocket Server is started at http://127.0.0.1:9501' . PHP_EOL;
});

$server->on('open', function(Server $server, Request $request) {
    echo 'Connection open: ' . $request->fd . PHP_EOL;
});

$server->on("request", function (Request $request, Response $response) {
    echo "Incoming connection time: " . date('Y-m-d H:i:s') . PHP_EOL;
    echo "Incoming connection uri: " . $request->server['request_uri'] . PHP_EOL;

    $html_content = file_get_contents(__DIR__ . '/index.html');

    $response->header("Content-Type", "text/html");
    $response->header("Charset", "UTF-8");
    $response->end($html_content);
});

$server->on('message', function(Server $server, Frame $frame) {
    echo 'Received message: ' . $frame->data . PHP_EOL;
    $server->push($frame->fd, 'Your message: ' . $frame->data);
});

$server->on('close', function(Server $server, $fd) {
    echo 'connection close: ' . $fd . PHP_EOL;
});

$server->start();
