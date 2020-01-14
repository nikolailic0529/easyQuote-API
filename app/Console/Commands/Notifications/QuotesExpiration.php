<?php

namespace App\Console\Commands\Notifications;

use Illuminate\Console\Command;
use App\Contracts\Repositories\{
    UserRepositoryInterface as User,
    Quote\QuoteDraftedRepositoryInterface as Quote
};
use App\Models\{
    Quote\Quote as QuoteModel,
    User as UserModel
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
    protected $user;

    /** @var \App\Contracts\Repositories\Quote\QuoteDraftedRepositoryInterface */
    protected $quote;

    /** @var \Carbon\CarbonInterval */
    protected $time;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, Quote $quote)
    {
        parent::__construct();

        $this->user = $user;
        $this->quote = $quote;
        $this->time = setting('notification_time');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \DB::transaction(function () {
            $this->user->cursor()
                ->each(function ($user) {
                    $this->serveUser($user);
                });
        });
    }

    protected function serveUser(UserModel $user): void
    {
        $this->quote->getExpiring($this->time, $user, $this->scope())
            ->each(function ($quote) {
                notification()
                    ->for($quote->user)
                    ->message($this->formatMessage($quote))
                    ->subject($quote)
                    ->url(ui_route('quotes.drafted.review', compact('quote')))
                    ->priority(2)
                    ->store();
            });
    }

    protected function formatMessage(QuoteModel $quote): string
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
