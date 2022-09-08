<?php

namespace App\Services\Mail;

use App\Services\Mail\Exceptions\MailRateLimitException;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Config\Repository;

class MailRateLimiter
{
    public function __construct(
        protected readonly RateLimiter $limiter,
        protected readonly Repository $config,
    ) {
    }

    public function remaining(): int
    {
        $key = $this->getLimiterKey();
        $maxAttempts = $this->getMaxAttempts();

        $key = $this->limiter->cleanRateLimiterKey($key);

        $attempts = $this->limiter->attempts($key);

        return $maxAttempts - $attempts;
    }

    public function attempt(): bool
    {
        $key = $this->getLimiterKey();
        $maxAttempts = $this->getMaxAttempts();
        $decaySeconds = $this->getDecaySeconds();

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return false;
        }

        return tap(true, function () use ($key, $decaySeconds) {
            $this->limiter->hit($key, $decaySeconds);
        });
    }

    public function tooManyAttempts(): bool
    {
        return $this->limiter->tooManyAttempts($this->getLimiterKey(), $this->getMaxAttempts());
    }

    public function clear(): void
    {
        $this->limiter->clear($this->getLimiterKey());
    }

    /**
     * @throws MailRateLimitException
     */
    public function attemptOrFail(): bool
    {
        return $this->attempt()
            ?: throw MailRateLimitException::limitExceeded(
                $this->getMaxAttempts(), $this->availableIn()
            );
    }

    public function availableIn(): int
    {
        return $this->limiter->availableIn($this->getLimiterKey());
    }

    protected function getLimiterKey(): string
    {
        return $this->config->get('mail.limiter.key');
    }

    public function getMaxAttempts(): int
    {
        return once(static fn(): int => (int) setting('mail_limit'));
    }

    public function getDecaySeconds(): int
    {
        return (int) $this->config->get('mail.limiter.decay_seconds');
    }
}