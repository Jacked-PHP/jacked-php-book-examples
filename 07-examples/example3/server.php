<?php

/**
 * This server consumes the data from the other server.
 *
 * This server process data requested asynchronously and present it.
 */

use OpenSwoole\Http\Server;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;

$server = new Server('0.0.0.0', 8080);

$server->set([
    'worker_num' => 1,
    'task_worker_num' => 2,
]);

$server->on('Request', function (Request $request, Response $response) use ($server) {
    $tasks[0] = ['filter' => 'name'];
    $tasks[1] = ['filter' => 'email'];

    $result = $server->taskCo($tasks, 1.5);

    $response->end('<pre>' . var_export($result, true) . '</pre>');
});

$server->on('Task', function (Server $server, int $task_id, int $worker_id, array $data) {
    $payload = file_get_contents('http://127.0.0.1:8181?filter=' . $data['filter']);

    $data['payload'] = $payload;
    $data['finished_at'] = time();
    $data['worker_id'] = $server->worker_id;

    return $data;
});

echo 'OpenSwoole http server is started at http://0.0.0.0:8080' . PHP_EOL;
$server->start();
