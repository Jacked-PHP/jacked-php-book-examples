<?php

/**
 * This examples has:
 * - Plates Template Engine.
 * - Dotenv for .env configurations.
 * - Slim\App for Http Handler.
 * - Ilex\SwoolePsr7 as PSR-7 adaptor.
 */

const ROOT_DIR = __DIR__;

require __DIR__ . '/vendor/autoload.php';

use App\Http\Controllers\LoginController;
use App\Http\Middlewares\AuthorizationMiddleware;
use App\Http\Middlewares\SessionMiddleware;
use Dotenv\Dotenv;
use Ilex\SwoolePsr7\SwooleResponseConverter;
use Ilex\SwoolePsr7\SwooleServerRequestConverter;
use Nyholm\Psr7\Factory\Psr17Factory;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\HTTP\Server;

// Load config.

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Section: Start Psr7 Converter.

$psr17Factory = new Psr17Factory();
$requestConverter = new SwooleServerRequestConverter(
    $psr17Factory,
    $psr17Factory,
    $psr17Factory,
    $psr17Factory
);

// Section: Start Request Handler (Slim).

$app = new App($psr17Factory);
$app->group('', function (RouteCollectorProxy $group) {
    $group->get('/', [LoginController::class, 'home']);

    $group->group('', function (RouteCollectorProxy $group2) {
        $group2->get('/login', [LoginController::class, 'login'])->setName('login');
        $group2->post('/login', [LoginController::class, 'loginHandler'])->setName('login-handler');
        $group2->post('/logout', LoginController::class . ':logoutHandler')->setName('logout-handler');
        $group2->get('/admin', [LoginController::class, 'admin'])
            ->setName('admin');
    })->add(new AuthorizationMiddleware);

})->add(new SessionMiddleware);
$app->addRoutingMiddleware();

// Swoole part.

$server_address = isset($_ENV['SERVER_ADDRESS']) ? $_ENV['SERVER_ADDRESS'] : '127.0.0.1';
$server_port = isset($_ENV['SERVER_PORT']) ? $_ENV['SERVER_PORT'] : '9503';

$server = new Server($server_address, $server_port);

$server->set([
    'document_root' => __DIR__ . '/public',
    'enable_static_handler' => true,
    'static_handler_locations' => ['/imgs', '/css'],
]);

$server->on("start", function (Server $server) use ($server_address, $server_port) {
    echo "HTTP server available at http://" . $server_address . ":" . $server_port . PHP_EOL;
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
