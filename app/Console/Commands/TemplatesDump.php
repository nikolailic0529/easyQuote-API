<?php

namespace App\Console\Commands;

use App\Models\QuoteTemplate\QuoteTemplate;
use Illuminate\Console\Command;

class TemplatesDump extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:templates-dump';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a dump of the user defined templates';

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
        $query = QuoteTemplate::nonSystem();

        $this->info('Backing up the user defined templates...');
        $this->info('Total Count: ' . $query->count());

        $templates = $query->with('templateFields', 'currency', 'user', 'company', 'vendor', 'countries')->get();
        $templates = serialize($templates);

        $path = 'templates/' . now()->format('m-d-y_h-m');
        storage_missing('templates') && storage_mkdir('templates');
        storage_put($path, $templates);

        $this->info('Backing up the user defined templates was completed!');
        $this->info('storage/app/' . $path);
    }
}
