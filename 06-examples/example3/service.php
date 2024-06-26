#!/usr/bin/env php
<?php
declare(strict_types=1);

use OpenSwoole\Coroutine as Co;

class DataObject
{
    public $total = 0;
    public $data = [];

    public function push($item)
    {
        $this->data[] = $item;
    }

    public function sum()
    {
        $this->total = array_sum($this->data);
    }
}

$data = new DataObject;

Co::run(function () use (&$data) {

    $co1 = go(function () use (&$data) {
        $data->push(1);
        Co::yield();
        $data->push(3);
        Co::yield();
        $data->push(5);
    });

    $co2 = go(function () use (&$data) {
        $data->push(2);
        Co::yield();
        $data->push(4);
        Co::yield();
        $data->push(6);
    });

    // process
    Co::resume($co1);
    Co::resume($co2);
    Co::resume($co1);
    Co::resume($co2);

});

echo implode(',', $data->data) . PHP_EOL;
