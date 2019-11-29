<?php

namespace App\Console\Commands;

use App\Models\{
    Vendor,
    Data\Country
};
use Illuminate\Console\Command;

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
                $vendor = Vendor::whereShortCode($vendorData['short_code'])->first();
                $countries = Country::whereIn('iso_3166_2', $vendorData['countries'])->get();
                $vendor->countries()->sync($countries);
                $vendor->createLogo($vendorData['logo'], true);

                $this->output->write('.');
            });
        });

        activity()->enableLogging();

        $this->info("\nSystem Defined Vendors were updated!");
    }
}
