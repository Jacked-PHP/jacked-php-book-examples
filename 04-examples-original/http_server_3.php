<?php

require __DIR__ . '/vendor/autoload.php';

use Swoole\HTTP\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use League\Plates\Engine;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$server_address = isset($_ENV['SERVER_ADDRESS']) ? $_ENV['SERVER_ADDRESS'] : '127.0.0.1';
$server_port = isset($_ENV['SERVER_PORT']) ? $_ENV['SERVER_PORT'] : '9503';

$server = new Server($server_address, $server_port);

$server->on("start", function (Server $server) use ($server_address, $server_port) {
    echo "HTTP server available at http://" . $server_address . ":" . $server_port . "\n";
});

$server->on("request", function (Request $request, Response $response) {
    echo "Incoming connection time: " . date('Y-m-d H:i:s') . "\n";
    echo "Incoming connection uri: " . $request->server['request_uri'] . "\n";

    $custom_content = '';
    if (null !== $request->get && isset($request->get['content'])) {
        $custom_content = $request->get['content'];
    }

    $templates = new Engine(__DIR__ . '/html');
    $html_content = $templates->render('sample1', [
        'main_heading' => 'My Page Title',
        'content' => 'The page\'s Body goes here... ' . $custom_content,
    ]);

    $response->header("Content-Type", "text/html");
    $response->header("Charset", "UTF-8");
    $response->end($html_content);
});

$server->on('close', function ($server) {
    echo "Connection closed.\n";
});

$server->start();
