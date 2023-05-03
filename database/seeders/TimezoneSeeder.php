<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TimezoneSeeder extends Seeder
{
    public function run(): void
    {
        $seeds = collect(
            json_decode(
                json: file_get_contents(__DIR__.'/models/timezones.json'),
                associative: true
            )
        );

        $seeds = $seeds->map(static function (array $seed): array {
            $seed['id'] = Str::orderedUuid()->toString();

            return $seed;
        });

        DB::transaction(static function () use ($seeds): void {
            foreach ($seeds as $seed) {
                $tz = DB::table('timezones')->where('text', $seed['text'])->first();

                if (!$tz) {
                    DB::table('timezones')
                        ->insert([
                            'id' => $seed['id'],
                            'abbr' => $seed['abbr'],
                            'utc' => $seed['utc'],
                            'text' => $seed['text'],
                            'value' => $seed['value'],
                            'offset' => $seed['offset'],
                        ]);

                    continue;
                }

                DB::table('timezones')
                    ->where('id', $tz->id)
                    ->update([
                        'abbr' => $seed['abbr'],
                        'utc' => $seed['utc'],
                        'text' => $seed['text'],
                        'value' => $seed['value'],
                        'offset' => $seed['offset'],
                    ]);
            }
        });
    }
}
