<?php namespace App\Console\Commands;

use App\Models\Quote\Margin\CountryMargin;
use Illuminate\Console\Command;
use DB;

class MarginsUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'margins:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Predefined Margins';

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
        $countryMargin = CountryMargin::first();
        $country_margin_id = $countryMargin->id;

        $margin_data = collect($countryMargin->only('value', 'method', 'is_fixed'))->put('type', 'By Country')->toJson();

        DB::table('quotes')->whereNotNull('country_margin_id')
            ->update(compact('country_margin_id', 'margin_data'));
    }
}
