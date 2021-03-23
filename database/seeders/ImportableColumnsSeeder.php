<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webpatser\Uuid\Uuid;

class ImportableColumnsSeeder extends Seeder
{
    /**
     * Run the database seeders.
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

        $importable_columns = json_decode(file_get_contents(__DIR__.'/models/importable_columns.json'), true);

        collect($importable_columns)->each(function ($column) {
            $columnId = (string)Uuid::generate(4);
            $now = now();

            DB::table('importable_columns')->insert([
                'id' => $columnId,
                'header' => $column['header'],
                'name' => $column['name'],
                'order' => $column['order'],
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'activated_at' => $now
            ]);

            collect($column['aliases'])->each(function ($alias) use ($columnId) {
                DB::table('importable_column_aliases')->insert([
                    'id' => (string)Uuid::generate(4),
                    'alias' => $alias,
                    'importable_column_id' => $columnId
                ]);
            });
        });
    }
}
