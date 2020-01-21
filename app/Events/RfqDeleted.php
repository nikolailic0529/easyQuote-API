<?php

namespace App\Events;

use App\Http\Resources\CustomerRepositoryResource;
use App\Models\Customer\Customer;
use Illuminate\Broadcasting\{
    InteractsWithSockets,
    PrivateChannel
};
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\LazyCollection;

class RfqDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    /** @var \App\Models\Customer\Customer */
    public $customer;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return $this->loggedInUsers()->map(function ($user) {
                return new PrivateChannel('user.'.$user->id);
            })->toArray();
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'customer.deleted';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return CustomerRepositoryResource::make($this->customer)->resolve();
    }

    private function loggedInUsers(): LazyCollection
    {
        return app('user.repository')->cursor(function (Builder $query) {
            $query->whereAlreadyLoggedIn(true);
        });
    }
}
