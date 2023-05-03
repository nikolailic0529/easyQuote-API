<?php

namespace App\Domain\Worldwide\Events\DistributorQuote;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DistributionProcessed implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public string $quoteKey;

    public string $distributionKey;

    public MessageBag $failures;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $quoteKey, string $distributionKey, MessageBag $failures)
    {
        $this->quoteKey = $quoteKey;
        $this->distributionKey = $distributionKey;
        $this->failures = $failures;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('ww-quote-import.'.$this->quoteKey);
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'ww-distribution.processed';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'worldwide_quote_id' => $this->quoteKey,
            'worldwide_distribution_id' => $this->distributionKey,
            'failures' => $this->failures->toArray(),
        ];
    }
}
