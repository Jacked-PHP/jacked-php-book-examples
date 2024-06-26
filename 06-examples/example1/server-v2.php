<?php

use OpenSwoole\HTTP\Server;
use OpenSwoole\Http\Response;
use OpenSwoole\Http\Request;
use OpenSwoole\Coroutine as Co;

$server = new Server('0.0.0.0', 8080, Server::POOL_MODE);

$server->on('start', function (Server $server) {
    $pid_file = __DIR__ . '/http-server-pid';
    if (file_exists($pid_file)) {
        unlink($pid_file);
    }
    file_put_contents($pid_file, $server->master_pid);
    echo 'Server started with PID: ' . $server->master_pid . ' at http://127.0.0.1:' . $server->port . PHP_EOL;
});

$server->on('request', function (Request $request, Response $response) {
    $response->header('Content-Type', 'application/json');

    Co::sleep(1);
    echo 'Executing...' . PHP_EOL;

    if (isset($request->get['user'])) {
        $response->end(json_encode([
            1 => [
                'title' => 'title 1',
                'content' => 'some content',
            ],
            2 => [
                'title' => 'title 2',
                'content' => 'some content 2',
            ],
        ]));
    } else if (isset($request->get['post_id'])) {
        $response->end(json_encode([
            1 => [
                'content' => 'some comment',
            ],
            2 => [
                'content' => 'some comment 2',
            ],
        ]));
    } else {
        $response->end(json_encode([
            1 => [
                'name' => 'John Galt',
                'email' => 'john@galt.com',
            ],
            2 => [
                'name' => 'Luke Skywalker',
                'email' => 'luke@skywalker.com',
            ],
            3 => [
                'name' => 'Luke Skywalker 2',
                'email' => 'luke@skywalker2.com',
            ],
        ]));
    }
});

$server->start();
