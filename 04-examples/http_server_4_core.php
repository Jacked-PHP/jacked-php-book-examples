<?php

/**
 * This examples has:
 * - Plates Template Engine.
 * - Dotenv for .env configurations.
 * - Slim\App for Http Handler (our PSR-15).
 * - OpenSwoole\Core as PSR-7 adaptor.
 * - Nyholm\Psr7 as PSR-17 HTTP Factory.
 */

const ROOT_DIR = __DIR__;

require __DIR__ . '/vendor/autoload.php';

use App\Http\Controllers\IndexController;
use Dotenv\Dotenv;
use Slim\App;
use Nyholm\Psr7\Factory\Psr17Factory;
use OpenSwoole\HTTP\Server;
use Psr\Http\Message\ServerRequestInterface;

// Load config.

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Section: Start Request Handler (Slim).

$app = new App(new Psr17Factory());
$app->get('/', [IndexController::class, 'index']);
$app->addRoutingMiddleware();

// Swoole part.

$server_address = isset($_ENV['SERVER_ADDRESS']) ? $_ENV['SERVER_ADDRESS'] : '127.0.0.1';
$server_port = isset($_ENV['SERVER_PORT']) ? $_ENV['SERVER_PORT'] : '9503';

$server = new Server($server_address, $server_port);

$server->set([
    'document_root' => __DIR__ . '/public',
    'enable_static_handler' => true,
    'static_handler_locations' => ['/imgs'],
]);

$server->on("start", function (Server $server) use ($server_address, $server_port) {
    echo "HTTP server available at http://" . $server_address . ":" . $server_port . "\n";
});

$server->handle(fn (ServerRequestInterface $request) => $app->handle($request));

$server->on('close', function ($server) {
    echo "Connection closed.\n";
});

$server->start();
