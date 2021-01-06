<?php

namespace App\Console\Commands;

use App\Models\{
    Vendor,
    Data\Country
};
use App\Services\ThumbnailManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VendorsUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:vendors-update';

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
     */
    public function handle()
    {
        $this->output->title("Updating System Defined Vendors...");

        activity()->disableLogging();

        $vendors = json_decode(file_get_contents(database_path('seeds/models/vendors.json')), true);

        DB::beginTransaction();

        $this->output->progressStart(count($vendors));

        collect($vendors)->each(function ($data) {
            /** @var Vendor */
            $vendor = Vendor::firstOrNew(['short_code' => $data['short_code']], ['name' => $data['name'], 'is_system' => true]);

            $vendor->save();

            $countries = Country::whereIn('iso_3166_2', $data['countries'])->get();

            $vendor->countries()->sync($countries);

            $vendor->createLogo($data['logo'], true);

            if (isset($data['svg_logo'])) {
                ThumbnailManager::updateModelSvgThumbnails($vendor, base_path($data['svg_logo']));
            }

            $this->output->progressAdvance();
        });

        $this->output->progressFinish();

        DB::commit();

        activity()->enableLogging();

        $this->info("System Defined Vendors were updated!");
    }
}
