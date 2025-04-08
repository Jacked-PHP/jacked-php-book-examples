<?php

include __DIR__ . '/vendor/autoload.php';

use JackedPhp\LiteConnect\Migration\MigrationManager;
use JackedPhp\LiteConnect\SQLiteFactory;
use OpenSwoole\Atomic;
use OpenSwoole\Core\Coroutine\Pool\ClientPool;
use OpenSwoole\Coroutine as Co;
use JackedPhp\LiteConnect\Connection\Connection;
use Migrations\CreateUsersTable;
use Models\User;
use OpenSwoole\Process;

$databasePath = __DIR__ . '/database/database.sqlite';
$connectionPool = new ClientPool(
    factory: SQLiteFactory::class,
    config: [
        'database' => $databasePath,
    ],
    size: 1,
);

function getConnection(ClientPool &$connectionPool): Connection {
    $connection = null;
    Co::run(function() use (&$connection, &$connectionPool) {
        $connection = $connectionPool->get();
    });

    return $connection;
}

function putConnection(ClientPool &$connectionPool, Connection $connection): void {
    Co::run(function() use (&$connection, &$connectionPool) {
        $connectionPool->put($connection);
    });
}

function runMigrations(Connection $connection): void {
    $migrationManager = new MigrationManager($connection);
    $migrationManager->runMigrations([new CreateUsersTable()]);
}

function createBaseUsers(Connection $connection): void {
    $user = new User($connection);
    $raw_data = file_get_contents(__DIR__ . '/data.json');
    $data = json_decode($raw_data, true);
    foreach ($data as $user_data) {
        $user->create($user_data);
    }
}

// always start fresh
if (file_exists($databasePath)) {
    unlink($databasePath);
    touch($databasePath);
}

$connection = getConnection($connectionPool);
runMigrations($connection);
createBaseUsers($connection);
putConnection($connectionPool, $connection);

// process being here

$counter = new Atomic(0);
$assertionsCounter = new Atomic(0);
$workerNum = 10;
$processes = [];

for ($i = 0; $i < $workerNum; $i++) {
    $process = new Process(function (Process $process) use (
        $workerNum,
        &$connectionPool,
        &$counter,
        &$assertionsCounter
    ) {
        $connection = $connectionPool->get();

        if ($connection->isConnected()) {
            $user = new User($connection);
            $expectedNewName = $process->pid . '-' . $counter->get();

            $currentUser = current($user->get());
            $currentUser->update(['name' => $expectedNewName]);

            $currentUser = current($user->get());
            if ($expectedNewName !== $currentUser->name) {
                $assertionsCounter->add(1);
            }
        }

        $counter->add(1);
        $connectionPool->put($connection);
        $process->write('bye! (from ' . $process->pid . ')');
    }, enableCoroutine: true);

    $process->useQueue();
    $process->start();
    $processes[$process->pid] = $process;
}

foreach ($processes as $pid => $process) {
    $result = $process->read();
    echo $result . PHP_EOL;
    Process::kill($process->pid);
}

echo '=====================' . PHP_EOL;
echo 'Total: ' . $counter->get() . PHP_EOL;
echo 'Assertion Errors: ' . $assertionsCounter->get() . PHP_EOL;
