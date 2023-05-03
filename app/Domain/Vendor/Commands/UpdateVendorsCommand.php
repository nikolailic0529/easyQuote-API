<?php

namespace App\Domain\Vendor\Commands;

use App\Domain\Country\Models\Country;
use App\Domain\Image\Services\ThumbHelper;
use App\Domain\Vendor\Models\Vendor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateVendorsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:update-vendors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Vendors Countries';

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
     *
     * @throws \Throwable
     */
    public function handle()
    {
        $this->output->title('Updating System Defined Vendors...');

        activity()->disableLogging();

        $vendors = yaml_parse_file(database_path('seeders/models/vendors.yaml'));

        DB::beginTransaction();

        $this->output->progressStart(count($vendors));

        $countryModelKeys = Country::query()->pluck((new Country())->getKeyName())->all();

        collect($vendors)->each(function ($data) use ($countryModelKeys) {
            /** @var \App\Domain\Vendor\Models\Vendor */
            $vendor = Vendor::firstOrNew(['short_code' => $data['short_code']], ['name' => $data['name'], 'is_system' => true]);

            $vendor->save();

            $vendor->countries()->sync($countryModelKeys);

            if (isset($data['logo'])) {
                $vendor->createLogo($data['logo'], true);
            }

            if (isset($data['svg_logo'])) {
                ThumbHelper::updateModelSvgThumbnails($vendor, base_path($data['svg_logo']));
            }

            $this->output->progressAdvance();
        });

        $this->output->progressFinish();

        DB::commit();

        activity()->enableLogging();

        $this->info('System Defined Vendors were updated!');

        return Command::SUCCESS;
    }
}
