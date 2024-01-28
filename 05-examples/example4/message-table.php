<?php

use OpenSwoole\Table;

return function(): Table {
    $table = new Table(1024, 1);
    $table->column('id', Swoole\Table::TYPE_INT);
    $table->column('fd', Swoole\Table::TYPE_INT);
    $table->column('user_name', Swoole\Table::TYPE_STRING, 50);
    $table->column('message', Swoole\Table::TYPE_STRING, 150);
    $table->create();
    return $table;
};
