<?php

namespace App\Domain\FailureReport;

use App\Domain\FailureReport\Contracts\RateLimiter;
use App\Domain\FailureReport\Mail\FailureReportMail;
use App\Foundation\Error\ErrorReporter;
use App\Foundation\Log\Contracts\LoggerAware;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Mail\MailQueue;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class MailErrorReporter implements ErrorReporter, LoggerAware
{
    protected array $dontReport = [
        \Illuminate\Http\Client\RequestException::class,
        \GuzzleHttp\Exception\RequestException::class,
        \App\Foundation\Mail\Exceptions\MailRateLimitException::class,
    ];

    public function __construct(
        protected readonly Cache $cache,
        protected readonly MailQueue $mailer,
        protected readonly RateLimiter $rateLimiter,
        protected LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function __invoke(\Throwable $e): void
    {
        if (!$this->shouldReport($e)) {
            return;
        }

        try {
            $key = $this->resolveReportableKey($e);

            $mailer = $this->mailer;

            $this->rateLimiter->attempt(
                key: $key,
                callback: static function () use ($mailer, $e): void {
                    $mailer->queue(new FailureReportMail($e));
                },
            );
        } catch (\Throwable $e) {
            $this->logger->error('Could not send the failure report mail.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function resolveReportableKey(\Throwable $e): string
    {
        return static::class.$e::class.sha1($e->getMessage());
    }

    protected function shouldReport(\Throwable $e): bool
    {
        foreach ($this->dontReport as $type) {
            if ($e instanceof $type) {
                return false;
            }
        }

        return true;
    }

    public function setLogger(LoggerInterface $logger): static
    {
        return tap($this, fn (): LoggerInterface => $this->logger = $logger);
    }
}
