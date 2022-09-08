<?php

namespace Tests\Unit;

use App\Mail\MailLimitExceededMail;
use App\Services\Mail\Exceptions\MailRateLimitException;
use App\Services\Mail\MailRateLimiter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Tests\Unit\Stubs\TestMailable;

class MailLimitTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test rate limiting of mail sent.
     */
    public function test_it_doesnt_allow_send_emails_over_the_set_limit(): void
    {
        $this->app[MailRateLimiter::class]->clear();

        Event::fake([MessageSent::class]);

        Mail::alwaysTo('test@easyquote.com');

        $this->app['db.connection']->table('system_settings')
            ->where('key', 'mail_limit')
            ->update(['value' => 5]);

        $this->assertSame(5, setting('mail_limit'));

        for ($i = 0; $i < 4; $i++) {
            Mail::send((new TestMailable()));
        }

        Event::assertDispatchedTimes(MessageSent::class, 4);

        $exception = null;

        try {
            Mail::send((new TestMailable()));
        } catch (\Throwable $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(MailRateLimitException::class, $exception);

        Event::assertDispatched(function (MessageSent $event) {
            return isset($event->data['__mailable']) && $event->data['__mailable'] === MailLimitExceededMail::class;
        }, 1);
    }
}
