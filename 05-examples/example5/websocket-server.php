<?php

use App\Actions\AddMessage;
use App\Actions\DeleteMessage;
use App\Middlewares\DeleteMessageMiddleware;
use Conveyor\Conveyor;
use OpenSwoole\Http\Response;
use OpenSwoole\Websocket\Server;
use OpenSwoole\Http\Request;
use OpenSwoole\WebSocket\Frame;

require __DIR__ . '/vendor/autoload.php';

$host = '0.0.0.0';
$port = 9501;

$server = new Server($host, $port);
$server->user_table = (require __DIR__ . DIRECTORY_SEPARATOR . 'user-table.php')();
$server->message_table = (require __DIR__ . DIRECTORY_SEPARATOR . 'message-table.php')();

$users = [
    1 => [
        'name' => 'John Doe',
        'token' => '123456', // http token
        'token2' => 'extra123456', // ws token (single use)
        'token2_used' => false,
    ],
    2 => [
        'name' => 'Jane Doe',
        'token' => '7891011', // http token
        'token2' => 'extra7891011', // ws token (single use)
        'token2_used' => false,
    ],
];

/**
 * @param array<array-key, array{id:int,name:string,token:string,token2:string,token2_used:bool}> $users
 * @param string $token
 * @return ?array{id:int,name:string,token:string,token2:string,token2_used:bool}
 */
function getUserByToken(array $users, string $token, bool $singleUse = false): ?int {
    foreach ($users as $key => $user) {
        if (!$singleUse && $user['token'] === $token) {
            return $key;
        } elseif ($singleUse && $user['token2'] === $token && !$user['token2_used']) {
            return $key;
        }
    }

    return null;
}

function processSecWebSocketKey(Request $request): array {
    $secWebSocketKey = $request->header['sec-websocket-key'];
    $patten = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';

    if (
        0 === preg_match($patten, $secWebSocketKey)
        || 16 !== strlen(base64_decode($secWebSocketKey))
    ) {
        throw new Exception('Invalid Sec-WebSocket-Key');
    }

    $key = base64_encode(sha1($request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

    $headers = [
        'Upgrade' => 'websocket',
        'Connection' => 'Upgrade',
        'Sec-WebSocket-Accept' => $key,
        'Sec-WebSocket-Version' => '13',
    ];

    if (isset($request->header['sec-websocket-protocol'])) {
        $headers['Sec-WebSocket-Protocol'] = $request->header['sec-websocket-protocol'];
    }

    return $headers;
}

$server->on("start", function (Server $server) {
    echo 'Swoole WebSocket Server is started at http://127.0.0.1:9501' . PHP_EOL;
});

$server->on('Request', function(Request $request, Response $response) use ($users)
{
    parse_str($request->server['query_string'] ?? '', $params);

    // verifying token
    if (!isset($params ['token']) || !$userId = getUserByToken($users, $params['token'])) {
        $response->end('Invalid token!');
        return;
    }

    $htmlPage = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'index.html');
    $htmlPage = str_replace('{{user-token}}', $users[$userId]['token2'], $htmlPage);
    $response->end($htmlPage);
});

$server->on('handshake', function(Request $request, Response $response) use ($server, &$users) {
    try {
        $headers = processSecWebSocketKey($request);
    } catch (Exception $e) {
        $response->status(400);
        $response->end($e->getMessage());
        return false;
    }

    parse_str($request->server['query_string'] ?? '', $params);

    if (
        !isset($params ['token'])
        || !$userId = getUserByToken($users, $params['token'], true)
    ) {
        $response->status(401);
        $response->end();
        echo "Invalid token!" . PHP_EOL;
        return false;
    }
    $users[$userId]['token2_used'] = true;

    $name = $users[$userId]['name'];

    $server->user_table->set($request->fd, [
        'id' => $request->fd,
        'name' => $name,
    ]);

    $server->defer(function() use ($request, $server, $name) {
        $server->push($request->fd, json_encode([
            'action' => 'set_user_name',
            'user_name' => $name,
        ]));
    });

    foreach ($headers as $headerKey => $val) {
        $response->header($headerKey, $val);
    }

    $response->status(101);
    $response->end();
    echo 'Connection open: ' . $request->fd . PHP_EOL;
    return true;
});

$server->on('message', function(Server $server, Frame $frame) {
    $user_name = $server->user_table->get($frame->fd, 'name');

    echo 'Received message (' . $user_name . '): ' . $frame->data . PHP_EOL;

    $addMessage = new AddMessage;
    $deleteMessage = new DeleteMessage;

    Conveyor::init()
        ->server($server)
        ->fd($frame->fd)
        ->persistence()
        ->addActions([$addMessage, $deleteMessage])
        ->addMiddlewareToAction($deleteMessage->getName(), new DeleteMessageMiddleware)
        ->run($frame->data);
});

$server->on('close', function(Server $server, $fd) {
    echo 'Connection close: ' . $fd . PHP_EOL;
    $server->user_table->del($fd);
});

$server->start();
