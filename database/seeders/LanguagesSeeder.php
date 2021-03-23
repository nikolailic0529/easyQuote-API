<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webpatser\Uuid\Uuid;

class LanguagesSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run()
    {
        //Empty the languages table
        Schema::disableForeignKeyConstraints();

        DB::table('languages')->delete();

        Schema::enableForeignKeyConstraints();

        $languages = json_decode(file_get_contents(__DIR__ . '/models/languages.json'), true);

        collect($languages)->each(function ($language) {
            DB::table('languages')->insert([
                'id' => (string) Uuid::generate(4),
                'code' => $language['code'],
                'name' => $language['name'],
                'native_name' => $language['nativeName'],
            ]);
        });
    }
}
