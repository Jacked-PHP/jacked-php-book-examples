<?php

namespace ServerManager;

use OpenSwoole\Process;
use OpenSwoole\Coroutine\System;
use OpenSwoole\Table;
use OpenSwoole\Coroutine as Co;
use OpenSwoole\Timer;

class Helper
{
    public static function startTable(): Table
    {
        $actions_table = new Table(1024);
        $actions_table->column('data', Table::TYPE_STRING, 64);
        $actions_table->create();

        return $actions_table;
    }

    /**
     * This method find the process id (pid) by the process name.
     * @return int|null
     */
    public static function getHttpServerPid(): ?int
    {
        // list processes by name (first)
        $pid = System::exec('/usr/bin/ps -aux | grep ' . HTTP_PROCESS_NAME . ' | grep -v \'grep ' . HTTP_PROCESS_NAME . '\' | /usr/bin/awk \'{ print $2; }\' | /usr/bin/sed -n \'1,1p\'');
        $clean_pid = trim($pid['output']);
        return (int) $clean_pid ?: null;
    }

    public static function startHttpServer(): void
    {
        $clean_pid = self::getHttpServerPid();
        if ($clean_pid === null) {
            System::exec('/usr/bin/php $(pwd)/server.php');
        }
    }

    public static function stopHttpServer(): void
    {
        echo 'Stopping...' . PHP_EOL;
        $clean_pid = self::getHttpServerPid();
        if ($clean_pid !== null) {
            Process::kill($clean_pid, SIGKILL);
        }
    }

    public static function listenKillSignal(): void
    {
        Co::run(function() {
            System::waitSignal(SIGKILL, -1);
        });
    }

    public static function startConfig(Table $actionsTable): void
    {
        $actionsTable->set(MANAGER_CONFIG_TABLE_KEY, [
            'data' => json_encode([
                /**
                 * Keep the Server Alive
                 */
                'keep_alive' => $_ENV['KEEP_ALIVE'] ?? false,
            ]),
        ]);
    }

    public static function getConfig(Table $actionsTable, string $key): ?string
    {
        $config = $actionsTable->get(MANAGER_CONFIG_TABLE_KEY, 'data');
        return json_decode($config, true)[$key] ?? null;
    }

    public static function setConfig(Table $actionsTable, string $key, string $value): void
    {
        $config = $actionsTable->get(MANAGER_CONFIG_TABLE_KEY, 'data');
        $parsedConfig = json_decode($config, true);

        $parsedConfig[$key] = $value;

        $actionsTable->set(MANAGER_CONFIG_TABLE_KEY, [
            'data' => json_encode($parsedConfig),
        ]);
    }

    public static function addWsTimer(Table $actionsTable, int $timer): void
    {
        $rawTimers = $actionsTable->get(WS_TIMERS_TABLE_KEY, 'data');
        $timers = json_decode($rawTimers, true) ?? [];
        $timers[] = $timer;

        $actionsTable->set(WS_TIMERS_TABLE_KEY, [
            'data' => json_encode($timers),
        ]);
    }

    public static function getWsTimers(Table $actionsTable): array
    {
        $timers = $actionsTable->get(WS_TIMERS_TABLE_KEY, 'data');
        return json_decode($timers, true) ?? [];
    }

    public static function clearWsTimers(Table $actionsTable): void
    {
        $timers = self::getWsTimers($actionsTable);
        foreach ($timers as $timer) {
            Timer::clear($timer);
        }

        $actionsTable->set(WS_TIMERS_TABLE_KEY, ['data' => '[]']);
    }
}
