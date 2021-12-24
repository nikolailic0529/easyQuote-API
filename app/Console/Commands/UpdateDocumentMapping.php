<?php

namespace App\Console\Commands;

use App\DTO\ImportableColumn\UpdateOrCreateColumnData;
use App\Models\Data\Country;
use App\Models\QuoteFile\ImportableColumn;
use App\Services\ImportableColumn\ImportableColumnEntityService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Throwable;

class UpdateDocumentMapping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:update-mapping';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update system document mapping';

    /**
     * Execute the console command.
     *
     * @return int
     * @throws Throwable
     */
    public function handle(): int
    {
        $this->output->title("Updating document mapping");

        $seeds = yaml_parse_file(database_path('seeders/models/importable_columns.yaml'));

        $seeds = array_map(function (array $seed) {
            $country = Country::query()->where('iso_3166_2', Arr::pull($seed, 'country'))->sole();

            return $seed
                + ['country_id' => $country->getKey()];

        }, $seeds);

        $this->withProgressBar($seeds, fn(array $seed) => $this->performColumnUpdate($seed));

        $this->output->newLine(2);
        $this->info("Document mapping has been updated");

        return self::SUCCESS;
    }

    protected function performColumnUpdate(array $seed): void
    {
        /** @var ImportableColumnEntityService $entityService */
        $entityService = $this->laravel->make(ImportableColumnEntityService::class);

        /** @var ImportableColumn $column */
        $column = ImportableColumn::query()
            ->where('name', $seed['name'])
            ->where('is_system', true)
            ->firstOrNew();

        $data = new UpdateOrCreateColumnData(array_merge(
            $seed, [
                'is_system' => true,
                'is_temp' => false,
            ]
        ));

        $entityService->updateOrCreateColumn($column, $data);
    }
}
