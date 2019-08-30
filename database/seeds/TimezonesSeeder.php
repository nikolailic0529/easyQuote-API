<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimezonesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Empty the timezones table
        DB::table('timezones')->delete();

        $timezones = json_decode(file_get_contents(__DIR__ . '/models/timezones.json'), true);

        collect($timezones)->each(function ($timezone) {
            DB::table('timezones')->insert([
                'id' => (string) Uuid::generate(4),
                'text' => $timezone['text'],
                'value' => $timezone['value'],
                'offset' => $timezone['offset'],
            ]);
        });
    }
}
