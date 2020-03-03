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
use Closure;
use Illuminate\Database\Eloquent\Builder;

class QuotesExpiration extends Command
{
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

    /** @var \App\Contracts\Repositories\UserRepositoryInterface */
    protected Users $users;

    /** @var \App\Contracts\Repositories\Quote\QuoteDraftedRepositoryInterface */
    protected Quotes $quotes;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Users $users, Quotes $quotes)
    {
        parent::__construct();

        $this->users = $users;
        $this->quotes = $quotes;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \DB::transaction(function () {
            $this->users->cursor()
                ->each(function ($user) {
                    $this->serveUser($user);
                });
        });
    }

    protected function serveUser(User $user): void
    {
        $this->quotes->getExpiring(setting('notification_time'), $user, $this->scope())
            ->each(function ($quote) {
                notification()
                    ->for($quote->user)
                    ->message($this->formatMessage($quote))
                    ->subject($quote)
                    ->url(ui_route('quotes.status', compact('quote')))
                    ->priority(2)
                    ->store();
            });
    }

    protected function formatMessage(Quote $quote): string
    {
        $expires_at = optional($quote->customer->validUntilAsDate)->format('d M');
        $rfq_number = $quote->rfq_number;

        return __(QE_01, compact('rfq_number', 'expires_at'));
    }

    protected function scope(): Closure
    {
        return function (Builder $query) {
            $query->doesntHave('notifications');
        };
    }
}
