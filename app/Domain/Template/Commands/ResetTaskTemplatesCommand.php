<?php

namespace App\Domain\Template\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ResetTaskTemplatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:reset-task-templates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset task templates if they are not defined';

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
        $this->info('Task templates maintenance...');

        $stores = app('task_template.manager')->getIterator();

        foreach ($stores as $store) {
            $filename = $store->getFileName();

            $directory = File::dirname($filename);

            /* Create directory with 0775 permissions if store directory does not exist. */
            File::ensureDirectoryExists($directory);

            /* Reset content to default if store file does not exist. */
            if (!File::exists($filename)) {
                $this->warn(sprintf('%s file does not exist. File will be reset to default.', class_basename($store)));

                tap($store->reset(), fn () => $this->info(sprintf('%s file has been reset.', class_basename($store))));

                continue;
            }

            $this->info(sprintf('%s file exist.', class_basename($store)));
        }
    }
}
