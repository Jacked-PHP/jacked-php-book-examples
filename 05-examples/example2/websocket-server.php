<?php

use OpenSwoole\Http\Response;
use OpenSwoole\Websocket\Server;
use OpenSwoole\Http\Request;
use OpenSwoole\WebSocket\Frame;

$server = new Server("0.0.0.0", 9501);
$server->table = (require __DIR__ . DIRECTORY_SEPARATOR . 'user-table.php')();

$server->on("start", function (Server $server) {
    echo 'OpenSwoole WebSocket Server is started at http://127.0.0.1:9501' . PHP_EOL;
});

$server->on('open', function(Server $server, Request $request) {
    if (!isset($request->server['query_string'])) {
        $server->disconnect($request->fd, 401, 'Please, inform your name for this connection.');
        return;
    }

    parse_str($request->server['query_string'], $parsed_query);
    $server->table->set($request->fd, ['id' => $request->fd, 'name' => $parsed_query['name']]);

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
    $user_name = $server->table->get($frame->fd, 'name');

    echo 'Received message (' . $user_name . '): ' . $frame->data . PHP_EOL;

    $connections = $server->connections;
    foreach ($connections as $fd) {
        if (!$server->isEstablished($fd)) {
            continue;
        }

        if ($frame->fd === $fd) {
            $server->push($fd, 'My message: ' . $frame->data);
            continue;
        }

        $server->push($fd, $user_name . '\'s message: ' . $frame->data);
    }
});

$server->on('close', function(Server $server, $fd) {
    echo 'Connection close: ' . $fd . PHP_EOL;
    $server->table->del($fd);
});

$server->start();
