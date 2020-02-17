<?php

namespace App\Console\Commands;

use App\Contracts\Repositories\CurrencyRepositoryInterface as Currencies;
use Illuminate\Console\Command;
use Arr;

class CurrenciesUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:currencies-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
    public function handle(Currencies $repository)
    {
        $currencies = json_decode(file_get_contents(database_path('seeds/models/currencies.json')), true);

        collect($currencies)->each(
            fn ($attributes) => $repository->firstOrCreate(Arr::only($attributes, 'code'), $attributes)
        );
    }
}
