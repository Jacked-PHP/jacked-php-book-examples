#!/usr/bin/php

<?php

use OpenSwoole\HTTP\Server;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;
use OpenSwoole\Coroutine\System;
use OpenSwoole\Coroutine as Co;
use OpenSwoole\Util;

include_once __DIR__ . '/constants.php';

Util::setProcessName(HTTP_PROCESS_NAME);

$port = 8181;

$server = new Server('0.0.0.0', $port);
$server->set([
    'worker_num' => 1,
]);
$server->on('start', function (Server $server) use ($port) {
    echo 'OpenSwoole http server is started at http://127.0.0.1:' . $port . PHP_EOL;
});
$server->on('shutdown', function (Server $server) {
    echo 'OpenSwoole http server is shutting down.' . PHP_EOL;
});
$server->on('request', function (Request $request, Response $response) {
    $response->header('Content-Type', 'text/plain');
    $response->end('Hello World' . PHP_EOL);
});
$server->start();

// Listening for the kill signal.
Co::run(function () {
    System::waitSignal(SIGKILL, -1);
});
