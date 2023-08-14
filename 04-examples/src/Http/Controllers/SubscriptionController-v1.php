<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasServerParams;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use League\Plates\Engine;

class SubscriptionController
{
    use HasServerParams;

    public function subscriptionForm(ServerRequestInterface $request, ResponseInterface $response)
    {
        $serverParams = $this->getServerParams($request);

        echo "Incoming connection time: " . date('Y-m-d H:i:s') . PHP_EOL;
        echo "Incoming connection uri: " . $serverParams['REQUEST_URI'] ?? '' . PHP_EOL;

        $templates = new Engine(ROOT_DIR . '/html');
        $html_content = $templates->render('sample5', [
            'main_heading' => 'Subscription Page',
        ]);

        $response->getBody()->write($html_content);
        return $response->withStatus(200);
    }

    public function subscribe(ServerRequestInterface $request, ResponseInterface $response)
    {
        $serverParams = $this->getServerParams($request);

        $data = $request->getParsedBody();

        echo "Incoming connection time: " . date('Y-m-d H:i:s') . PHP_EOL;
        echo "Incoming connection uri: " . $serverParams['REQUEST_URI'] ?? '' . PHP_EOL;
        echo "Incoming connection data: " . json_encode($data) . PHP_EOL;

        $templates = new Engine(ROOT_DIR . '/html');
        $html_content = $templates->render('sample5-result', [
            'main_heading' => 'Subscription Page Result',
            'email' => $data['email'],
        ]);

        $response->getBody()->write($html_content);
        return $response->withStatus(200);
    }
}
