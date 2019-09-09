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
        //Empty the importable_columns and importable_column_aliases tables
        Schema::disableForeignKeyConstraints();

        DB::table('importable_columns')->delete();
        DB::table('importable_column_aliases')->delete();

        Schema::enableForeignKeyConstraints();

        $importable_columns = json_decode(file_get_contents(__DIR__ . '/models/importable_columns.json'), true);

        collect($importable_columns)->each(function ($column) {
            $columnId = (string) Uuid::generate(4);

            DB::table('importable_columns')->insert([
                'id' => $columnId,
                'header' => $column['header'],
                'name' => $column['name'],
                'regexp' => $column['regexp'],
                'order' => $column['order']
            ]);
            
            collect($column['aliases'])->each(function ($alias) use ($columnId) {
                DB::table('importable_column_aliases')->insert([
                    'id' => (string) Uuid::generate(4),
                    'alias' => $alias,
                    'importable_column_id' => $columnId
                ]);
            });
        });
    }
}
