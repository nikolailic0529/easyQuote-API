<?php

namespace App\Console\Commands;

use App\Models\QuoteFile\ImportableColumn;
use Illuminate\Console\Command;

class ImportableColumnsUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:parser-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update System Defined Importable Columns';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info("Updating System Defined Importable Columns...");

        activity()->disableLogging();

        \DB::transaction(function () {
            $importable_columns = json_decode(file_get_contents(database_path('seeds/models/importable_columns.json')), true);

            collect($importable_columns)->each(function ($column) {
                $importableColumn = ImportableColumn::firstOrCreate(
                    ['name' => $column['name'], 'is_system' => true],
                    $column
                );

                collect($column['aliases'])->each(function ($alias) use ($importableColumn) {
                    $importableColumn->aliases()->firstOrCreate(compact('alias'));
                });

                $this->output->write('.');
            });
        });

        activity()->enableLogging();

        $this->info("\nSystem Defined Importable Columns were updated!");
    }
}
