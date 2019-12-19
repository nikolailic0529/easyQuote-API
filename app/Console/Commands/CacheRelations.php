<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Quote\Quote;

class CacheRelations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:cache-relations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache models relations';

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
    public function handle()
    {
        activity()->disableLogging();

        $this->comment('Caching relations...');

        $bar = $this->output->createProgressBar(Quote::count());

        Quote::withCacheableRelations()->chunk(500, function ($chunk) use ($bar) {
            foreach ($chunk as $entry) {
                $entry->cacheRelations();
                $bar->advance();
            }
        });

        $bar->finish();

        $this->info("\nDone!");

        activity()->enableLogging();
    }
}
