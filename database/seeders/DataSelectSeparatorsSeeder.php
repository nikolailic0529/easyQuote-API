<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webpatser\Uuid\Uuid;

class DataSelectSeparatorsSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run()
    {
        // Empty the data_select_separators table
        Schema::disableForeignKeyConstraints();

        DB::table('data_select_separators')->delete();

        Schema::enableForeignKeyConstraints();

        $separators = json_decode(file_get_contents(__DIR__.'/models/data_select_separators.json'), true);

        collect($separators)->each(function ($separator) {
            DB::table('data_select_separators')->insert([
                'id' => (string) Uuid::generate(4),
                'name' => $separator['name'],
                'separator' => $separator['separator'],
            ]);
        });
    }
}
