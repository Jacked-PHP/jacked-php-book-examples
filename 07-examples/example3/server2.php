<?php

/**
 * This server just serves data.
 *
 * This service returns or emails or names depending on the filter.
 */

use OpenSwoole\Http\Server;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;
use OpenSwoole\Coroutine as Co;

$server = new Server('0.0.0.0', 8181);

$server->on('Request', function (Request $request, Response $response) use ($server) {

    $input_data = $request->get;
    $accepted_filters = ['name', 'email'];

    if (
        empty($input_data)
        || !isset($input_data['filter'])
        || !in_array($input_data['filter'], $accepted_filters)
    ) {
        $response->header('Content-Type', 'application/json');
        $response->status(400);
        $response->end(json_encode([
            'success' => false,
            'message' => 'Missing or invalid required parameter: filter.',
        ]));
    }

    if ($input_data['filter'] === 'email') {
        $data = [
            [
                'email' => 'johngalt@wordstree.com',
            ],
            [
                'email' => 'hariseldon@wordstree.com',
            ],
        ];
    } else {
        $data = [
            [
                'name' => 'John Galt',
            ],
            [
                'name' => 'Hari Seldon',
            ],
        ];
    }

    Co::sleep(1);

    $response->header('Content-Type', 'application/json');
    $response->end(json_encode($data));
});

echo 'OpenSwoole http server is started at http://0.0.0.0:8181' . PHP_EOL;
$server->start();
