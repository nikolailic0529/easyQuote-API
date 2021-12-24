<?php

namespace App\Console\Commands;

use App\Models\Template\{
    QuoteTemplate,
    ContractTemplate
};
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Str;

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
        collect([QuoteTemplate::nonSystem(), ContractTemplate::nonSystem()])
            ->each(fn (Builder $query) => $this->dumpTemplates($query));
    }

    protected function dumpTemplates(Builder $query)
    {
        $modelsName = Str::plural(class_basename($query->getModel()));
        $subdir = Str::snake($modelsName);

        $this->info(PHP_EOL . 'Backing up the user defined ' . $modelsName . '...');
        $this->info('Total Count: ' . $query->count());

        $templates = $query->with('templateFields', 'currency', 'user', 'company', 'vendor', 'countries')->get();
        $templates = serialize($templates);

        $dir = 'templates' . DIRECTORY_SEPARATOR . $subdir;

        $path = $dir . DIRECTORY_SEPARATOR . now()->format('m-d-y_h-m');

        storage_missing($dir) && storage_mkdir($dir);
        storage_put($path, $templates);

        $this->info('Backing up the user defined ' . $modelsName . ' was completed!');
        $this->info('storage/app/' . $path);
    }
}
