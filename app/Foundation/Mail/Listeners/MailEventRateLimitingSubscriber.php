<?php

namespace App\Foundation\Mail\Listeners;

use App\Foundation\Mail\Mail\MailLimitExceededMail;
use App\Foundation\Mail\Services\MailRateLimiter;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Mail;

class MailEventRateLimitingSubscriber
{
    protected array $dontHit = [
        MailLimitExceededMail::class,
    ];

    public function __construct(
        protected readonly LockProvider $lockProvider,
        protected readonly Config $config,
        protected readonly Cache $cache,
        protected readonly MailRateLimiter $mailRateLimiter,
        protected readonly RateLimiter $rateLimiter,
    ) {
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(MessageSending::class,
            [static::class, 'sendMailLimitExceededNotification'],
        );

        $events->listen(MessageSending::class,
            [static::class, 'attemptMailSending'],
        );
    }

    protected function shouldntHit(MessageSending $event): bool
    {
        return isset($event->data['__mailable'])
            && in_array($event->data['__mailable'], $this->dontHit, true);
    }

    public function sendMailLimitExceededNotification(MessageSending $event): void
    {
        if (!$this->config->get('mail.limiter.enabled')) {
            return;
        }

        if ($this->shouldntHit($event)) {
            return;
        }

        // Send email notification about exceeded limit only once
        if ($this->mailRateLimiter->remaining() === 0) {
            $this->rateLimiter->attempt(
                key: MailLimitExceededMail::class.$this->mailRateLimiter->getMaxAttempts(),
                maxAttempts: 1,
                callback: function (): void {
                    Mail::send(new MailLimitExceededMail(
                        limit: $this->mailRateLimiter->getMaxAttempts(),
                        remaining: $this->mailRateLimiter->remaining()
                    ));
                }
            );
        }
    }

    public function attemptMailSending(MessageSending $event): bool|null
    {
        if (!$this->config->get('mail.limiter.enabled')) {
            return null;
        }

        if ($this->shouldntHit($event)) {
            return null;
        }

        return $this->mailRateLimiter->attempt();
    }
}
