<?php

namespace App\Domain\Team\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamDeleted
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    private \App\Domain\Team\Models\Team $team;

    /**
     * Create a new event instance.
     */
    public function __construct(\App\Domain\Team\Models\Team $team)
    {
        $this->team = $team;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
