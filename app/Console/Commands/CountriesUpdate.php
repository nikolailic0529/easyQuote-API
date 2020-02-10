<?php

namespace App\Console\Commands;
use App\Contracts\Repositories\{
    CountryRepositoryInterface as Countries,
    CurrencyRepositoryInterface as Currencies
};
use Illuminate\Console\Command;
use Arr;

class CountriesUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:countries-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing system countries';

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
    public function handle(Countries $countries, Currencies $currencies)
    {
        activity()->disableLogging();

        $countriesList = json_decode(file_get_contents(database_path('seeds/models/countries.json')), true);

        \DB::transaction(function () use ($countriesList, $countries, $currencies) {
            collect($countriesList)->each(function ($attributes) use ($countries, $currencies) {
                $currency = $currencies->findByCode(optional($attributes)['currency_code']);

                $attributes = ['default_currency_id' => optional($currency)->id, 'currency_name' => optional($currency)->name] + $attributes;

                $countries->updateOrCreate(Arr::only($attributes, 'iso_3166_2'), $attributes);
            });
        });

        activity()->enableLogging();
    }
}
