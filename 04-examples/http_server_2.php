<?php

use OpenSwoole\HTTP\Server;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;

$server = new Server("127.0.0.1", 9503);

$server->on("start", function (Server $server) {
    echo "HTTP server available at http://127.0.0.1:9503\n";
});

$server->on("request", function (Request $request, Response $response) {
    echo "Incoming connection time: " . date('Y-m-d H:i:s') . PHP_EOL;
    echo "Incoming connection uri: " . $request->server['request_uri'] . PHP_EOL;

    $html_content = <<<HTML
<html>
<head>
    <meta charset="UTF-8" />
    <title>HTML Swoole Server</title>
</head>
<body>
    <h1>My Page Title</h1>
    <div>The page's Body goes here</div>
</body>
</html>
HTML;

    $response->header("Content-Type", "text/html");
    $response->header("Charset", "UTF-8");
    $response->end($html_content);
});

$server->on('close', function ($server) {
    echo "Connection closed.\n";
});

$server->start();
