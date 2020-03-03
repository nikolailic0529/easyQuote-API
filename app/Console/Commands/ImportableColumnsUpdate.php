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

        $importableColumns = json_decode(file_get_contents(database_path('seeds/models/importable_columns.json')), true);

        $importableColumns = collect($importableColumns)->transform(function ($attributes) {
            $country_id = app('country.repository')->findIdByCode($attributes['country']);
            return compact('country_id') + $attributes;
        });

        \DB::transaction(function () use ($importableColumns) {
            $importableColumns->each(function ($attributes) {
                $importableColumn = ImportableColumn::firstOrCreate(
                    ['name' => $attributes['name'], 'is_system' => true],
                    $attributes
                );

                $importableColumn->update($attributes);

                collect($attributes['aliases'])->unique()->each(
                    fn ($alias) => $importableColumn->aliases()->firstOrCreate(compact('alias'))
                );

                $this->deleteDuplicatedAliases($importableColumn);

                $this->output->write('.');
            });
        });

        activity()->enableLogging();

        $this->info("\nSystem Defined Importable Columns were updated!");
    }

    protected function deleteDuplicatedAliases(ImportableColumn $importableColumn): void
    {
        $aliases = $importableColumn->aliases()->get()->toBase();

        $duplicates = $aliases->duplicates('alias');

        $duplicatedIds = $aliases
            ->filter(fn ($alias, $key) => $duplicates->get($key, false))
            ->pluck('id');

        $importableColumn->aliases()->whereIn('id', $duplicatedIds)->forceDelete();
    }
}
