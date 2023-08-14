<?php

use OpenSwoole\HTTP\Server;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;

$server = new Server("127.0.0.1", 9503);

$server->on("start", function (Server $server) {
    echo "HTTP server available at http://127.0.0.1:9503\n";
});

$server->on("request", function (Request $request, Response $response) {
    echo "Incoming connection time: " . date('Y-m-d H:i:s') . "\n";
    echo "Incoming connection uri: " . $request->server['request_uri'] . "\n";
    $response->header("Content-Type", "text/plain");
    $response->end("Here it all starts.\n");
});

$server->on('close', function ($server) {
    echo "Connection closed.\n";
});

$server->start();
