<?php

namespace App\Actions;

use Conveyor\Actions\Abstractions\AbstractAction;

class DeleteMessage extends AbstractAction
{
    protected string $name = 'delete-message';

    public function execute(array $data): mixed
    {
        $this->server->message_table->del($data['message_id']);

        foreach ($this->server->connections as $fd) {
            $this->server->push($fd, json_encode([
                'action' => $this->name,
                'delete_message_id' => $data['message_id'],
            ]));
        }

        return null;
    }

    public function validateData(array $data): void
    {
        // TODO: Implement validateData() method.
    }
}
