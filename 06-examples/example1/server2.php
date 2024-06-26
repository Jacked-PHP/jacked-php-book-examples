<?php

include __DIR__ . '/vendor/autoload.php';

use OpenSwoole\Core\Coroutine\WaitGroup;
use OpenSwoole\Http\Server;
use OpenSwoole\Http\Response;
use OpenSwoole\Http\Request;
use OpenSwoole\Runtime;
use OpenSwoole\Coroutine as Co;

Co::set(['hook_flags' => Runtime::HOOK_NATIVE_CURL]);

$server = new Server('0.0.0.0', 8181, Server::POOL_MODE);

$server->on('start', function (Server $server) {
    $pid_file = __DIR__ . '/http-server-pid2';
    if (file_exists($pid_file)) {
        unlink($pid_file);
    }
    file_put_contents($pid_file, $server->master_pid);
    echo 'Server started with PID: ' . $server->master_pid . ' at http://127.0.0.1:' . $server->port . PHP_EOL;
});

function get_users(): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'localhost:8080');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($ch);
    curl_close($ch);
    return json_decode($data, true);
}

function get_users_posts(string $email): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'localhost:8080?user=1');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($ch);
    curl_close($ch);
    return json_decode($data, true);
}

$server->on('request', function (Request $request, Response $response) {
    $wg = new WaitGroup();

    $start = microtime(true);

    /** @var array{name: string, email: string} $users */
    $users = get_users(); // ~1 sec

    foreach ($users as $key => $user) {
        go(function () use (&$users, $user, $key, $wg) {
            $wg->add();
            $posts = get_users_posts($user['email']); // ~1 sec
            $users[$key]['posts'] = $posts;
            $wg->done();
        });
    }

    $wg->wait(5);

    $end = microtime(true);

    echo 'Execution Time: ' . ($end - $start) . PHP_EOL;

    $response->end(json_encode($users));
});

$server->start();
