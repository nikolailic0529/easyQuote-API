<?php

use Illuminate\Database\Seeder;

class ImportableColumnsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Empty the importable_columns table
        Schema::disableForeignKeyConstraints();

        DB::table('importable_columns')->delete();

        Schema::enableForeignKeyConstraints();

        $importable_columns = json_decode(file_get_contents(__DIR__ . '/models/importable_columns.json'), true);

        collect($importable_columns)->each(function ($column) {
            DB::table('importable_columns')->insert([
                'id' => (string) Uuid::generate(4),
                'header' => $column['header'],
                'alias' => $column['alias'],
                'regexp' => $column['regexp'],
                'order' => $column['order'],
            ]);
        });
    }
}
