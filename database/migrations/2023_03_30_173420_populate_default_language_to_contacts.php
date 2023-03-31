<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

return new class() extends Migration {
    public function up(): void
    {
        Artisan::call('db:seed', [
            '--force' => true,
            '--class' => \Database\Seeders\LanguageSeeder::class,
        ]);

        $lang = DB::table('languages')
            ->where('code', 'en')
            ->first();

        if (!$lang) {
            throw new LogicException('Could not find language [en].');
        }

        DB::table('contacts')
            ->whereNull('language_id')
            ->update(['language_id' => $lang->id]);
    }

    public function down(): void
    {
    }
};
