<?php

namespace App\Console\Commands;

use App\Contracts\Repositories\Quote\QuoteRepositoryInterface;
use App\Models\Quote\Quote;
use Illuminate\Console\Command;

class QuotesResubmit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:quotes-resubmit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resubmit yerly submitted quotes';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(QuoteRepositoryInterface $quoteRepository)
    {
        Quote::submitted()->cursor()->each(function ($quote) use ($quoteRepository) {
            $quoteRepository->submit($quote);
        });
    }
}
