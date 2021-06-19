<?php

/**
 * This examples has:
 * - Plates Template Engine.
 * - Dotenv for .env configurations.
 * - Slim\App for Http Handler.
 * - Ilex\SwoolePsr7 as PSR-7 adaptor.
 */

require __DIR__ . '/vendor/autoload.php';

use App\IndexController;
use App\SubscriptionController;
use Dotenv\Dotenv;
use Ilex\SwoolePsr7\SwooleResponseConverter;
use Ilex\SwoolePsr7\SwooleServerRequestConverter;
use Slim\App;
use Nyholm\Psr7\Factory\Psr17Factory;
use Swoole\HTTP\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;

// Load config.

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Start Psr7 Converter.

$psr17Factory = new Psr17Factory();
$requestConverter = new SwooleServerRequestConverter(
    $psr17Factory,
    $psr17Factory,
    $psr17Factory,
    $psr17Factory
);

// Start Request Handler (Slim).

$app = new App($psr17Factory);
$app->get('/', [IndexController::class, 'index']);
$app->get('/subscription', [SubscriptionController::class, 'subscriptionForm']);
$app->post('/subscription', [SubscriptionController::class, 'subscribe']);
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

$server->on("request", function (Request $request, Response $response) use ($app, $requestConverter) {
    $psr7Request = $requestConverter->createFromSwoole($request);
    $psr7Response = $app->handle($psr7Request);
    $converter = new SwooleResponseConverter($response);
    $converter->send($psr7Response);
});

$server->on('close', function ($server) {
    echo "Connection closed.\n";
});

$server->start();
