<?php

use OpenSwoole\Coroutine;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;
use OpenSwoole\HTTP\Server;

$server_address = $_ENV['SERVER_ADDRESS'] ?? '127.0.0.1';
$server_port = $_ENV['SERVER_PORT'] ?? '9503';

$server = new Server($server_address, $server_port);

$server->set([
    'document_root' => __DIR__ . '/public',
    'enable_static_handler' => true,
    'static_handler_locations' => ['/imgs', '/css'],
]);

$server->on("start", function (Server $server) use ($server_address, $server_port) {
    echo "HTTP server available at http://" . $server_address . ":" . $server_port . PHP_EOL;
});

$server->on("request", function (Request $request, Response $response) {
    $response->header('Content-Type', 'text/event-stream');
    $response->header('Cache-Control', 'no-cache');
    $response->header('Connection', 'keep-alive');

    // write a socrates quote
    $text = "Hello, I'm a server-sent event: 'The only true wisdom is in knowing you know nothing.' - Socrates\n\n";

    $words = explode(' ', $text);

    foreach ($words as $word) {
        $response->write($word . ' ');

        // This is the example with the browser recognizing
        // the separate chunks in the inspector:
        // $response->write("data: {$word}\n\n");

        Coroutine::usleep(100000);
    }
    $response->end();
});

$server->on('close', function ($server) {
    echo "Connection closed.\n";
});

$server->start();
