<?php

use OpenSwoole\HTTP\Server;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;

if (!defined('PID_FILE')) {
    define('PID_FILE', './http-server-pid');
}

if (file_exists(PID_FILE)) {
    unlink(PID_FILE);
}

$server = new Server('0.0.0.0', 8001);

$server->on('start', function (Server $server) {
    file_put_contents(PID_FILE, $server->master_pid);
    echo 'HTTP server available at http://127.0.0.1:9503 (PID ' . $server->master_pid . ')' . PHP_EOL;
});

$server->on('request', function (Request $request, Response $response) {
    echo 'Incoming connection time: ' . date('Y-m-d H:i:s') . PHP_EOL;
    echo 'Incoming connection uri: ' . $request->server['request_uri'] . PHP_EOL;
    $response->header('Content-Type', 'text/plain');
    $response->end('Server response content.');
});

$server->on('close', function (Server $server) {
    echo 'Connection closed.\n';
});

$server->start();
