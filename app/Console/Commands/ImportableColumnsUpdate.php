<?php namespace App\Console\Commands;

use App\Contracts\Repositories\QuoteFile\ImportableColumnRepositoryInterface as ImportableColumnRepository;
use Illuminate\Console\Command;

class ImportableColumnsUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'importablecolumns:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Importable Columns Aliases';

    /**
     * Importable Column Repository
     *
     * @var \App\Repositories\QuoteFile\ImportableColumnRepository
     */
    protected $importableColumn;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ImportableColumnRepository $importableColumn)
    {
        parent::__construct();

        $this->importableColumn = $importableColumn;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $importable_columns = json_decode(file_get_contents(database_path('seeds/models/importable_columns.json')), true);

        collect($importable_columns)->each(function ($column) {
            $importableColumn = $this->importableColumn->findByName($column['name']);

            collect($column['aliases'])->each(function ($alias) use ($importableColumn) {
                $importableColumn->aliases()->firstOrCreate(compact('alias'));
                $this->output->write('.');
            });
        });

        $this->info("\nImportable Columns Aliases were updated!");
    }
}
