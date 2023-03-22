<?php

namespace App\Foundation\Mail\Listeners;

use App\Foundation\Mail\Mail\MailLimitExceededMail;
use App\Foundation\Mail\Services\MailRateLimiter;
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
        protected readonly MailRateLimiter $rateLimiter,
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

        $this->lockProvider->lock(__METHOD__, 10)
            ->block(10, function () {
                // Send email notification about exceeded limit only once
                if ($this->rateLimiter->remaining() === 0) {
                    if ($this->cache->add(MailLimitExceededMail::class.$this->rateLimiter->getMaxAttempts(), true,
                        $this->rateLimiter->availableIn())) {
                        Mail::send(new MailLimitExceededMail(
                            limit: $this->rateLimiter->getMaxAttempts(),
                            remaining: $this->rateLimiter->remaining()
                        ));
                    }
                }
            });
    }

    public function attemptMailSending(MessageSending $event): bool|null
    {
        if (!$this->config->get('mail.limiter.enabled')) {
            return null;
        }

        if ($this->shouldntHit($event)) {
            return null;
        }

        return $this->rateLimiter->attempt();
    }
}
