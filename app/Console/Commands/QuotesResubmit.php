<?php

namespace App\Console\Commands;

use App\Contracts\Repositories\Quote\QuoteRepositoryInterface;
use App\Models\Quote\Quote;
use Illuminate\Console\Command;
use Storage;

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
    protected $description = 'Resubmit early submitted quotes';

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
        $this->info("Resubmitting the Quotes...");

        activity()->disableLogging();

        Quote::submitted()->cursor()->each(function ($quote) use ($quoteRepository) {
            if (filled($quote->generatedPdf->original_file_path) || Storage::exists($quote->generatedPdf->original_file_path)) {
                $this->output->write('-');
                return true;
            }

            rescue(function () use ($quoteRepository, $quote) {
                $quoteRepository->submit($quote);
                $this->output->write('.');
            });
        });

        activity()->enableLogging();

        $this->info("\nThe Quotes were resubmitted!");
    }
}
