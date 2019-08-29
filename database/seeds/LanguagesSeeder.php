<?php

use Illuminate\Database\Seeder;

class LanguagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
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
