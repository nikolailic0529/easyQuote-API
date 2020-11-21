<?php

namespace Tests\Unit;

use App\Events\Slack\Sent;
use Event;
use Tests\TestCase;

class SlackTest extends TestCase
{
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
