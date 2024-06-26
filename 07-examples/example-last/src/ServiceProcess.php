<?php

namespace ServerManager;

use OpenSwoole\Process;
use OpenSwoole\Table;
use OpenSwoole\Util;

abstract class ServiceProcess
{
    /** @var array<int> */
    protected array $pids;

    public function __construct(
        protected Table $actionsTable,
    ) {}

    protected function setProcessName(string $name): void
    {
        Util::setProcessName($name);
    }

    public function run(?callable $callback = null): void
    {
        echo "Starting " . static::class . "..." . PHP_EOL;
        $process = new Process(function (Process $worker) use ($callback) {
            if ($callback) {
                $callback();
                return;
            }
            $this->callback();
        });
        $this->pids[] = $process->start();
    }

    abstract public function callback(): void;

    public function stop(): void
    {
        foreach ($this->pids as $pid) {
            Process::kill($pid, SIGKILL);
        }
    }
}
