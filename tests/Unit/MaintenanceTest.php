<?php

namespace Tests\Unit;

use App\Events\{
    MaintenanceCompleted,
    MaintenanceScheduled,
    MaintenanceStarted,
};
use App\Events\Slack\Sent;
use App\Jobs\UpMaintenance;
use App\Models\{User, System\Build};
use Illuminate\Support\{Carbon, Facades\Event};
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    /**
     * Test Maintenance Scheduling.
     *
     * @return void
     */
    public function testMaintenanceScheduling()
    {
        config(['services.slack.enabled' => true]);

        $startTime = Carbon::now()->addMinute();
        $endTime = (clone $startTime)->addMinute();
        $autoComplete = true;

        Event::fake([MaintenanceScheduled::class, MaintenanceStarted::class, MaintenanceCompleted::class, Sent::class]);

        Build::latest()->firstOrCreate([
            'start_time' => $startTime,
            'end_time' => $endTime
        ]);

        UpMaintenance::dispatchNow(
            $startTime, $endTime, $autoComplete
        );

        Event::assertDispatched(MaintenanceScheduled::class, function (MaintenanceScheduled $event) use ($startTime) {
            $this->assertEquals($event->schedule, $startTime);

            return true;
        });

        Event::assertDispatchedTimes(MaintenanceScheduled::class, User::count());

        Event::assertDispatched(MaintenanceStarted::class, function (MaintenanceStarted $event) use ($endTime) {
            $this->assertEquals($event->time, $endTime);

            return true;
        });

        Event::assertDispatchedTimes(MaintenanceStarted::class, User::count());

        Event::assertDispatched(MaintenanceCompleted::class, 1);

        Event::assertDispatchedTimes(Sent::class, 2);
    }
}
