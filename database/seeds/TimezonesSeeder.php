<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use App\Models\Data\Timezone;

class TimezonesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $timezones = json_decode(file_get_contents(database_path('seeds/models/timezones.json')), true);

        DB::transaction(
            fn () =>
            collect($timezones)
                ->each(fn ($timezone) => Timezone::updateOrCreate(
                    Arr::only($timezone, 'text'),
                    [
                        'abbr' => $timezone['abbr'],
                        'utc' => head($timezone['utc']),
                        'text' => $timezone['text'],
                        'value' => $timezone['value'],
                        'offset' => $timezone['offset']
                    ]
                ))
        );
    }
}
