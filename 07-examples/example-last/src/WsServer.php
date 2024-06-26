<?php

namespace ServerManager;

use League\Plates\Engine;
use OpenSwoole\Coroutine as Co;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;
use OpenSwoole\Table;
use OpenSwoole\Timer;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\WebSocket\Server;

class WsServer extends ServiceProcess
{
    public function __construct(
        Table $actionsTable,
        protected string $host = '0.0.0.0',
        protected int $port = 8080,
    ) {
        parent::__construct($actionsTable);

        $this->run([$this, 'startReactionTimer']);
    }

    public function callback(): void
    {
        $this->setProcessName(WS_PROCESS_NAME);

        $server = new Server($this->host, $this->port);

        $server->set([
            'document_root' => ROOT_DIR,
            'enable_static_handler' => true,
            'static_handler_locations' => ['/public'],
        ]);

        $server->on('open', fn (Server $s, Request $r) => $this->onOpen($s, $r));

        $server->on('request', fn (Request $req, Response $res) => $this->onRequest($req, $res));

        $server->on('message', fn (Server $s, Frame $f) => $this->onMessage($s, $f));

        $server->on('close', fn (Server $server, $fd) => $this->onClose($server, $fd));

        $server->start();
    }

    protected function onMessageTick(Server $server, Request $request): void
    {
        $status = $this->actionsTable->get(HTTP_STATUS_TABLE_KEY, 'data');
        $currentState = $this->actionsTable->get(HTTP_TEMP_STATUS_TABLE_KEY, 'data');

        if ($currentState === $status) {
            return;
        }

        echo 'Sending status: ' . $status . PHP_EOL;
        $this->actionsTable->set(HTTP_TEMP_STATUS_TABLE_KEY, [
            'data' => $status,
        ]);

        foreach ($server->connections as $fd) {
            echo "Sending status to: $fd\n";
            if ($server->isEstablished($fd)) {
                $server->push($fd, json_encode([
                    'id' => $fd,
                    'data' => [
                        'event' => 'server-status',
                        'data' => $status,
                    ],
                ]));
            }
        }
    }

    protected function onReactionTick(): void
    {
        $data = $this->actionsTable->get(WS_TABLE_KEY, 'data');
        $this->actionsTable->del(WS_TABLE_KEY);

        if (!$data) {
            return;
        }

        $parsedData = json_decode($data, true);
        if (!$parsedData) {
            return;
        }

        switch ($parsedData['event']) {
            case 'start':
                Helper::startHttpServer();
                break;
            case 'stop':
                Helper::stopHttpServer();
                break;
            case 'keep-alive':
                Helper::setConfig($this->actionsTable, KEEP_ALIVE, $parsedData['data']['keepAlive'] ? 'true' : 'false');
        }
    }

    protected function startReactionTimer(): void
    {
        Co::run(function () {
            $timer = Timer::tick(TIMER_INTERVAL, fn () => $this->onReactionTick());
            Helper::addWsTimer($this->actionsTable, $timer);
        });
    }

    protected function onOpen(Server $server, Request $request): void
    {
        echo 'Connection open: ' . $request->fd . PHP_EOL;

        $timer = Timer::tick(TIMER_INTERVAL, fn() => $this->onMessageTick($server, $request));
        Helper::addWsTimer($this->actionsTable, $timer);

        // Let's send the current status to the client.
        $server->defer(function () use ($server, $request) {
            // send server status
            $server->push($request->fd, json_encode([
                'id' => $request->fd,
                'data' => [
                    'event' => 'server-status',
                    'data' => $this->actionsTable->get(HTTP_STATUS_TABLE_KEY, 'data') ?? SERVER_DEAD,
                ],
            ]));

            // send keep alive status
            $server->push($request->fd, json_encode([
                'id' => $request->fd,
                'data' => [
                    'event' => 'keep-alive',
                    'data' => Helper::getConfig($this->actionsTable, KEEP_ALIVE) ?? 'false',
                ],
            ]));
        });
    }

    protected function onRequest(Request $request, Response $response): void
    {
        $templates = new Engine(ROOT_DIR . '/public');
        $response->end($templates->render('index', []));
    }

    protected function onMessage(Server $server, Frame $frame): void
    {
        echo 'Received message: ' . $frame->data . PHP_EOL;
        $this->actionsTable->set(WS_TABLE_KEY, [
            'data' => $frame->data,
        ]);
    }

    protected function onClose(Server $server, int $fd): void
    {
        echo 'Connection close: ' . $fd . PHP_EOL;
        Helper::clearWsTimers($this->actionsTable);
    }

    public function stop(): void
    {
        parent::stop();
        Helper::clearWsTimers($this->actionsTable);
    }
}
