<?php

namespace Tests\Unit;

use App\Domain\FailureReport\Mail\FailureReportMail;
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

    public function testReportsFailure(): void
    {
        Mail::fake();

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
