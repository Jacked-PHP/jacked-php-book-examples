<?php

use Dotenv\Dotenv;
use ServerManager\Helper;
use ServerManager\Monitor;
use ServerManager\WsServer;
use ServerManager\HttpServer;

const ROOT_DIR = __DIR__;

include_once __DIR__ . '/vendor/autoload.php';

// Load config.
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

include_once __DIR__ . '/constants.php';

$actionsTable = Helper::startTable();

Helper::startConfig($actionsTable);

// Process 1: WebSocket server.
(new WsServer($actionsTable))->run();

// Process 2: HTTP server.
(new HttpServer($actionsTable))->run();

// Process 3: Monitor.
(new Monitor($actionsTable))->run();

Helper::listenKillSignal();
