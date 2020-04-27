<?php

namespace App\Events\Permission;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GrantedModulePermission
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $result;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $result)
    {
        $this->result = $result;
    }
}
