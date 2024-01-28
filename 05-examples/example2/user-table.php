<?php

use OpenSwoole\Table;

return function(): Table {
    $table = new Table(1024, 1);
    $table->column('id', Table::TYPE_INT);
    $table->column('name', Table::TYPE_STRING, 64);
    $table->create();
    return $table;
};
