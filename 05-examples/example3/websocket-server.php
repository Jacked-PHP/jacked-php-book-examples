<?php

use App\Actions\AddMessage;
use App\Actions\DeleteMessage;
use Conveyor\Conveyor;
use OpenSwoole\Websocket\Server;
use OpenSwoole\Http\Request;
use OpenSwoole\WebSocket\Frame;

require __DIR__ . '/vendor/autoload.php';

$server = new Server("0.0.0.0", 9501);
$server->user_table = (require __DIR__ . DIRECTORY_SEPARATOR . 'user-table.php')();
$server->message_table = (require __DIR__ . DIRECTORY_SEPARATOR . 'message-table.php')();

$server->on("start", function (Server $server) {
    echo 'Swoole WebSocket Server is started at http://127.0.0.1:9501' . PHP_EOL;
});

$server->on('open', function(Server $server, Request $request) {
    if (!isset($request->server['query_string'])) {
        $server->disconnect($request->fd, 401, 'Please, inform your name for this connection.');
        return;
    }

    parse_str($request->server['query_string'], $parsed_query);
    $server->user_table->set($request->fd, ['id' => $request->fd, 'name' => $parsed_query['name']]);

    echo 'Connection open: ' . $request->fd . PHP_EOL;
});

$server->on('message', function(Server $server, Frame $frame) {
    $user_name = $server->user_table->get($frame->fd, 'name');

    echo 'Received message (' . $user_name . '): ' . $frame->data . PHP_EOL;

    Conveyor::init()
        ->server($server)
        ->fd($frame->fd)
        ->persistence()
        ->addActions([
            new AddMessage,
            new DeleteMessage,
        ])
        ->run($frame->data);
});

$server->on('close', function(Server $server, $fd) {
    echo 'Connection close: ' . $fd . PHP_EOL;
    $server->user_table->del($fd);
});

$server->start();
