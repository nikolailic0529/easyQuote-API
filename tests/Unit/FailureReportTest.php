<?php

namespace Tests\Unit;

use App\Domain\FailureReport\Mail\FailureReportMail;
use App\Domain\Settings\Models\SystemSetting;
use App\Domain\User\Models\User;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @group build
 */
class FailureReportTest extends TestCase
{
    public function testRendersFailureReportMailable(): void
    {
        $e = new \InvalidArgumentException(Str::random(100));
        $html = (new FailureReportMail($e))->render();

        $this->assertStringContainsString($e->getMessage(), $html);
    }

    public function testDoesntReportFailureWhenRecipientsUndefined(): void
    {
        Mail::fake();

        $prop = SystemSetting::query()
            ->where('key', 'failure_report_recipients')
            ->sole();

        $failureRecipient = User::factory()->create();
        $prop->value = [];
        $prop->save();

        /** @var ExceptionHandler $handler */
        $handler = $this->app->make(ExceptionHandler::class);

        $e = new \InvalidArgumentException(Str::random(100));

        $handler->report($e);

        Mail::assertNothingQueued();
        Mail::assertNothingSent();
    }

    public function testReportsFailure(): void
    {
        Mail::fake();

        $prop = SystemSetting::query()
            ->where('key', 'failure_report_recipients')
            ->sole();

        $failureRecipient = User::factory()->create();
        $prop->value = [$failureRecipient->getKey()];
        $prop->save();

        /** @var ExceptionHandler $handler */
        $handler = $this->app->make(ExceptionHandler::class);

        $e = new \InvalidArgumentException(Str::random(100));

        $handler->report($e);

        Mail::assertQueued(FailureReportMail::class, 1);
    }

    public function testDoesntReportTheSameFailureTwiceInTheSameMinute(): void
    {
        Mail::fake();

        /** @var ExceptionHandler $handler */
        $handler = $this->app->make(ExceptionHandler::class);

        $e = new \InvalidArgumentException(Str::random(100));

        $handler->report($e);
        $handler->report($e);

        Mail::assertQueued(FailureReportMail::class, 1);
    }

    public function testReportsTheSameFailureAfterAMinute(): void
    {
        Mail::fake();

        /** @var ExceptionHandler $handler */
        $handler = $this->app->make(ExceptionHandler::class);

        $e = new \InvalidArgumentException(Str::random(100));

        $handler->report($e);

        Mail::assertQueued(FailureReportMail::class, 1);

        $this->travelTo(now()->addSeconds(61));

        Mail::fake();

        $handler->report($e);

        Mail::assertQueued(FailureReportMail::class, 1);
    }
}
