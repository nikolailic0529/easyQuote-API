<?php

namespace App\Console\Commands;

use App\Services\QuoteReimportService;
use Illuminate\Console\Command;

class ReimportQuoteFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:reimport-quote {quote_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manual Quote Files Import';

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
    public function handle(QuoteReimportService $service)
    {
        $id = $this->argument('quote_id');

        $service->withOutput($this->output)->performReimportOfQuote($id);

        return 0;
    }
}
