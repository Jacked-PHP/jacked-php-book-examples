<?php

use OpenSwoole\Coroutine as Co;
use OpenSwoole\Coroutine\Channel;

$chan = new Channel(1);

function get_users(): array
{
    $raw_data = file_get_contents(__DIR__ . '/data.json');
    return json_decode($raw_data, true);
}

Co::run(function () use ($chan) {

    // Coroutine producing data.
    go(function () use ($chan) {
        $i = 0;
        while (1) {
            system('clear');
            echo $i . PHP_EOL;
            Co::sleep(1.0);
            $users = get_users();
            foreach ($users as $user) {
                $chan->push($user['name']);
                Co::sleep(1.0);
            }
            Co::sleep(10.0);
            $i++;
        }
    });

    // Coroutine printing data.
    go(function () use ($chan) {
        while (1) {
            $user_name = $chan->pop();
            echo 'User:' . $user_name . PHP_EOL;
        }
    });

});
