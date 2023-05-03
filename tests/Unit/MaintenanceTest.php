<?php

namespace Tests\Unit;

use App\Domain\Build\Models\Build;
use App\Domain\Maintenance\Events\MaintenanceCompleted;
use App\Domain\Maintenance\Events\MaintenanceScheduled;
use App\Domain\Maintenance\Events\MaintenanceStarted;
use App\Domain\Maintenance\Jobs\DownIntoMaintenanceMode;
use App\Domain\Slack\Events\Sent;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test Maintenance Scheduling.
     *
     * @return void
     */
    public function testMaintenanceScheduling()
    {
        $this->markTestSkipped();

        config(['services.slack.enabled' => true]);

        $startTime = Carbon::now()->addMinute();
        $endTime = (clone $startTime)->addMinute();
        $autoComplete = true;

        Event::fake([MaintenanceScheduled::class, MaintenanceStarted::class, MaintenanceCompleted::class, Sent::class]);

        Build::latest()->firstOrCreate([
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        DownIntoMaintenanceMode::dispatchNow(
            $startTime,
            $endTime,
            $autoComplete
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
