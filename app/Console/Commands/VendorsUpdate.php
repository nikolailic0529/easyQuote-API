<?php

namespace App\Console\Commands;

use App\Models\{
    Vendor,
    Data\Country
};
use App\Services\ThumbnailManager;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

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
        $this->info("Updating System Defined Vendors...");

        activity()->disableLogging();

        \DB::transaction(function () {
            $vendors = json_decode(file_get_contents(database_path('seeds/models/vendors.json')), true);

            collect($vendors)->each(function ($vendorData) {
                /** @var Vendor */
                $vendor = Vendor::whereShortCode($vendorData['short_code'])->first();
                $countries = Country::whereIn('iso_3166_2', $vendorData['countries'])->get();
                $vendor->countries()->sync($countries);
                $vendor->createLogo($vendorData['logo'], true);

                if (isset($vendorData['svg_logo'])) {
                    ThumbnailManager::updateModelSvgThumbnails($vendor, base_path($vendorData['svg_logo']));
                }

                $this->output->write('.');
            });
        });

        activity()->enableLogging();

        $this->info("\nSystem Defined Vendors were updated!");
    }
}
