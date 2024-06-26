<?php

namespace ServerManager;

use OpenSwoole\Coroutine as Co;
use OpenSwoole\Timer;

class Monitor extends ServiceProcess
{
    public function callback(): void
    {
        Co::run(fn() => Timer::tick(TIMER_INTERVAL, function () {
            $keepAlive = Helper::getConfig($this->actionsTable, KEEP_ALIVE);
            $keepAlive = $keepAlive === 'true';

            $status = $this->getServerStatus();
            $currentStatus = $this->actionsTable->get(HTTP_MONITOR_TEMP_STATUS_TABLE_KEY, 'data');
            $changed = $currentStatus !== $status;

            if (!$keepAlive && !$changed) {
                return;
            }

            $this->actionsTable->set(HTTP_MONITOR_TEMP_STATUS_TABLE_KEY, [
                'data' => $status,
            ]);

            $this->actionsTable->set(HTTP_STATUS_TABLE_KEY, [
                'data' => $status,
            ]);

            if ($keepAlive && $status === SERVER_DEAD) {
                Helper::startHttpServer();
            }
        }));
    }

    private function getServerStatus(): string
    {
        $clean_pid = Helper::getHttpServerPid();

        return $clean_pid === null ? SERVER_DEAD : SERVER_ALIVE;
    }
}
