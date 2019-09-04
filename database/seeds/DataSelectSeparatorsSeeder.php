<?php

use Illuminate\Database\Seeder;

class DataSelectSeparatorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Empty the data_select_separators table
        Schema::disableForeignKeyConstraints();

        DB::table('data_select_separators')->delete();

        Schema::enableForeignKeyConstraints();

        $separators = json_decode(file_get_contents(__DIR__ . '/models/data_select_separators.json'), true);

        collect($separators)->each(function ($separator) {
            DB::table('data_select_separators')->insert([
                'id' => (string) Uuid::generate(4),
                'name' => $separator['name'],
                'separator' => $separator['separator']
            ]);
        });
    }
}
