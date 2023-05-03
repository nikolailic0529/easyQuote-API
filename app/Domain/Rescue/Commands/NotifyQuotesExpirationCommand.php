<?php

namespace App\Domain\Rescue\Commands;

use App\Domain\Rescue\Contracts\QuoteDraftedRepositoryInterface;
use App\Domain\Rescue\Models\Quote;
use App\Domain\Rescue\Notifications\QuoteExpiresNotification;
use App\Domain\User\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class NotifyQuotesExpirationCommand extends Command
{
    const NOTIFICATION_KEY = 'expired';

    /**
     * @var string
     */
    protected $signature = 'eq:notify-quotes-expiration';

    /**
     * @var string
     */
    protected $description = 'Notify Users about Quotes Expiration';

    /**
     * Execute the job.
     */
    public function handle(): int
    {
        $cursor = User::query()->lazyById();

        foreach ($cursor as $user) {
            $this->serveUser($user);
        }

        return self::SUCCESS;
    }

    protected function serveUser(User $user): void
    {
        /** @var QuoteDraftedRepositoryInterface $quoteRepository */
        $quoteRepository = $this->getLaravel()->make(QuoteDraftedRepositoryInterface::class);

        $quoteRepository->getExpiring(setting('notification_time'), $user, static::scope())
            ->each(function (Quote $quote): void {
                if ($quote->user) {
                    $quote->user->notify(new QuoteExpiresNotification($quote));
                    $quote->notifications()->create(['notification_key' => static::NOTIFICATION_KEY]);
                }
            });
    }

    protected static function scope(): \Closure
    {
        return fn (Builder $query) => $query->whereDoesntHave(
            'notifications',
            fn (Builder $query) => $query->whereNotificationKey(static::NOTIFICATION_KEY)
        );
    }
}
