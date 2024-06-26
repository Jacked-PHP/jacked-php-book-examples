<?php

namespace ServerManager;

use OpenSwoole\Table;
use OpenSwoole\Coroutine as Co;

class HttpServer extends ServiceProcess
{
    public function __construct(
        Table $actionsTable,
        protected string $host = '0.0.0.0',
        protected int $port = 8181,
    ) {
        parent::__construct($actionsTable);
    }

    public function callback(): void
    {
        Co::run(fn() => Helper::startHttpServer());
    }
}
