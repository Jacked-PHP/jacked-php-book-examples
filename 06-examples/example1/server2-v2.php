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

$server->set([
    'enable_coroutine' => true,
    'max_coroutine' => 3000,
]);

$server->on('start', function (Server $server) {
    $pid_file = __DIR__ . '/http-server-pid2';
    if (file_exists($pid_file)) {
        unlink($pid_file);
    }
    file_put_contents($pid_file, $server->master_pid);
    echo 'Server started with PID: ' . $server->master_pid . ' at http://127.0.0.1:' . $server->port . PHP_EOL;
});

function curl_request(string $url): array
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($ch);
    curl_close($ch);
    return json_decode($data, true);
}

function get_users(): array
{
    return curl_request("localhost:8080");
}

function get_users_posts(string $email): array
{
    return curl_request("localhost:8080?user=1");
}

function get_posts_comments(int $post_id): array
{
    return curl_request("localhost:8080?post_id=" . $post_id);
}

$server->on('request', function (Request $request, Response $response) {
    $wg = new WaitGroup();

    $start = microtime(true);

    $users = get_users(); // ~1 sec

    foreach ($users as $key => $user) {
        go(function () use (&$users, $user, $key, $wg, $start) {
            $wg->add();
            $posts = get_users_posts($user['email']); // ~1 sec

            $wg2 = new WaitGroup();

            foreach ($posts as $post_id => $post) {
                go(function () use (&$posts, $post_id, $wg2) {
                    $wg2->add();
                    $comments = get_posts_comments($post_id); // ~1 sec
                    $posts[$post_id]['comments'] = $comments;
                    $wg2->done();
                });
            }

            $wg2->wait(5);

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
