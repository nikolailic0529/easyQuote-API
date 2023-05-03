<?php

namespace App\Domain\Rescue\Events\Customer;

use App\Domain\Rescue\Models\Customer;
use App\Domain\Rescue\Resources\V1\CustomerRepositoryResource;
use App\Domain\User\Models\{User};
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\LazyCollection;

class RfqDeleted implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    public Customer $customer;

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
        return $this->loggedInUsers()
            ->map(fn (User $user) => new PrivateChannel('user.'.$user->getKey()))
            ->toArray();
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
        return app('user.repository')->cursor(fn (Builder $query) => $query->whereAlreadyLoggedIn(true));
    }
}
