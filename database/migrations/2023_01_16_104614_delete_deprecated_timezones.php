<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration {
    public function up(): void
    {
        if (DB::table('timezones')->doesntExist()) {
            return;
        }

        $default = '(UTC) Coordinated Universal Time';

        $defaultTz = DB::table('timezones')->where('text', $default)->sole();

        $deprecated = [
            '(UTC-12:00) International Date Line West',
            '(UTC-02:00) Mid-Atlantic - Old',
        ];

        $deprecatedTimezones = DB::table('timezones')
            ->whereIn('text', $deprecated)
            ->get();

        DB::transaction(static function () use ($defaultTz, $deprecatedTimezones): void {
            foreach ($deprecatedTimezones as $tz) {
                DB::table('users')
                    ->where('timezone_id', $tz->id)
                    ->update(['timezone_id' => $defaultTz->id]);

                DB::table('timezones')
                    ->where('id', $tz->id)
                    ->delete();
            }
        });
    }

    public function down(): void
    {
    }
};
