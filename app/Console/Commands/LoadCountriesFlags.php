<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\RequestOptions;
use Arr, File;

class LoadCountriesFlags extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:load-countries-flags';

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
    public function handle(Guzzle $guzzle)
    {
        $response = $guzzle->get('https://restcountries.eu/rest/v2/all');

        $countries = collect(json_decode($response->getBody(), true));

        if ($countries->isEmpty()) {
            return;
        }

        $bar = $this->output->createProgressBar($countries->count());

        $dir = public_path('img/countries');

        File::makeDirectory($dir);

        $countries->each(function ($country) use ($guzzle, $dir, $bar) {
            $url = optional($country)['flag'];
            $path = $dir . DIRECTORY_SEPARATOR . File::basename($url);
            $file = fopen($path, 'w+');

            $guzzle->get($country['flag'], [
                RequestOptions::SINK => $file
            ]);

            $bar->advance();
        });

        $bar->finish();
    }
}
