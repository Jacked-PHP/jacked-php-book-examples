<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasServerParams;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use League\Plates\Engine;

class IndexController
{
    use HasServerParams;

    public function index(ServerRequestInterface $request, ResponseInterface $response)
    {
        $serverParams = $this->getServerParams($request);

        echo "Incoming connection time: " . date('Y-m-d H:i:s') . PHP_EOL;
        echo "Incoming connection uri: " . $serverParams['request_uri'] ?? '' . PHP_EOL;

        $query = $request->getQueryParams();

        $templates = new Engine(ROOT_DIR . '/html');
        $html_content = $templates->render('sample4', [
            'main_heading' => 'My Page Title',
            'content' => 'The page\'s Body goes here... ' . ($query['content'] ?? ''),
        ]);

        $response->getBody()->write($html_content);
        return $response->withStatus(200);


    }
}
