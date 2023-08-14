<?php

namespace App\Http\Controllers\Traits;

use Psr\Http\Message\ServerRequestInterface;

trait HasServerParams
{
    protected function getServerParams(ServerRequestInterface $request)
    {
        $serverParams = [];
        foreach ($request->getServerParams() as $key => $value) {
            $serverParams[strtolower($key)] = $value;
        }
        return $serverParams;
    }
}
