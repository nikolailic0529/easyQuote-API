<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Events\Slack\Sent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;

class SlackTest extends TestCase
{
    use DatabaseTransactions;
    
    /**
     * Test Slack Message sending.
     *
     * @return void
     */
    public function testMessageSending()
    {
        config(['services.slack.enabled' => true]);

        Event::fake(Sent::class);

        $slack = slack()
            ->title('TEST')
            ->status(['TEST']);
            
        $slack->send();

        Event::assertDispatched(Sent::class);
    }
}
