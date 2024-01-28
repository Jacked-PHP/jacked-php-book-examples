<?php

namespace App\Actions;

use Conveyor\Actions\Abstractions\AbstractAction;

class AddMessage extends AbstractAction
{
    protected string $name = 'add-message';

    public function execute(array $data): mixed
    {
        $user_name = $this->server->user_table->get($this->fd, 'name');

        $next_id = $this->server->message_table->count() + 1;
        $this->server->message_table->set($next_id, [
            'fd' => $this->fd,
            'user_name' => $user_name,
            'message' => $data['message'],
        ]);

        foreach ($this->server->connections as $fd) {
            if ($this->fd === $fd) {
                $message = json_encode([
                    'id' => $next_id,
                    'action' => $this->name,
                    'message' => 'My message: ' . $data['message'],
                ]);
            } else {
                $message = json_encode([
                    'id' => $next_id,
                    'action' => $this->name,
                    'message' => $user_name . '\'s message: ' . $data['message'],
                ]);
            }
            $this->server->push($fd, $message);
        }

        return null;
    }

    public function validateData(array $data): void
    {
        // TODO: Implement validateData() method.
    }
}
