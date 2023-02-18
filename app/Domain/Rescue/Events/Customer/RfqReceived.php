<?php

namespace App\Domain\Rescue\Events\Customer;

use App\Domain\Rescue\Models\Customer;
use App\Domain\Rescue\Resources\V1\CustomerBroadcastResource;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\LazyCollection;

class RfqReceived implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    public Customer $customer;

    public string $service;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Customer $customer, string $service)
    {
        $this->customer = $customer;
        $this->service = $service;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return $this->loggedInUsers()->map(fn ($user) => new PrivateChannel('user.'.$user->id))->toArray();
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'customer.created';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return CustomerBroadcastResource::make($this->customer)->resolve();
    }

    private function loggedInUsers(): LazyCollection
    {
        return app('user.repository')->cursor(fn (Builder $query) => $query->whereAlreadyLoggedIn(true));
    }
}
