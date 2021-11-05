<?php

namespace App\Console\Commands\Routine\Notifications;

use Illuminate\Console\Command;
use App\Contracts\Repositories\{
    UserRepositoryInterface as Users,
    Quote\QuoteDraftedRepositoryInterface as Quotes
};
use App\Models\{
    Quote\Quote,
    User
};
use Illuminate\Database\Eloquent\Builder;
use DB, Closure;

class QuotesExpiration extends Command
{
    public const NOTIFICATION_KEY = 'expired';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:notify-quotes-expiration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify Users about Quotes Expiration';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the job.
     *
     * @return int
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
        /** @var Quotes */
        $quotes = app(Quotes::class);

        $quotes->getExpiring(setting('notification_time'), $user, static::scope())
            ->each(function (Quote $quote) {
                notification()
                    ->for($quote->user)
                    ->message($this->formatMessage($quote))
                    ->subject($quote)
                    ->url(ui_route('quotes.status', compact('quote')))
                    ->priority(2)
                    ->store();

                $quote->notifications()->create(['notification_key' => static::NOTIFICATION_KEY]);
            });
    }

    protected function formatMessage(Quote $quote): string
    {
        $expires_at = optional($quote->customer->validUntilAsDate)->format('d M');
        $rfq_number = $quote->rfq_number;

        return __(QE_01, compact('rfq_number', 'expires_at'));
    }

    protected static function scope(): Closure
    {
        return fn (Builder $query) =>
        $query->whereDoesntHave(
            'notifications',
            fn (Builder $query) => $query->whereNotificationKey(static::NOTIFICATION_KEY)
        );
    }
}
