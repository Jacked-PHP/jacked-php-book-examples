<?php

use OpenSwoole\Coroutine;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;
use OpenSwoole\HTTP\Server;

$server_address = $_ENV['SERVER_ADDRESS'] ?? '127.0.0.1';
$server_port = $_ENV['SERVER_PORT'] ?? '9503';

$server = new Server($server_address, $server_port);

$server->on("start", function (Server $server) use ($server_address, $server_port) {
    echo "HTTP server available at http://". $server_address . ":". $server_port . PHP_EOL;
});

$server->on("request", function (Request $request, Response $response) {
    if ($request->server['request_uri'] === '/fe') {
        $html_content = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>SSE Test</title>
</head>
<body>
	<h1>Server-Sent Events Test</h1>

	<div id="events">
	    <strong>Streaming data: </strong>
		<p></p>
	</div>

	<script>
	    const eventSource = new EventSource("/");

	    eventSource.onmessage = function(event) {
			const eventContainer = document.getElementById("events");
			const paragraph = eventContainer.querySelector("p");
			if (event.data === "END") {
				eventSource.close();
				return;
			}
			paragraph.textContent += event.data + " ";
	    };

	    eventSource.onerror = function() {
			console.error("An error occurred with the SSE connection.");
		};
	</script>
</body>
</html>
HTML;
        $response->header("Content-Type", "text/html");
        $response->end($html_content);
        return;
    }

    $response->header('Content-Type', 'text/event-stream');
    $response->header('Cache-Control', 'no-cache');
    $response->header('Connection', 'keep-alive');

    // write a Socrates quote
    $text = "Hello, I'm a server-sent event: 'The only true wisdom is in knowing you know nothing.' - Socrates\n\n";

    $words = explode(' ', $text);

    foreach ($words as $word) {
        $response->write("data: {$word}\n\n");
        Coroutine::usleep(100000);
    }
    $response->end();
});

$server->on('close', function ($server) {
    echo "Connection closed.\n";
});

$server->start();