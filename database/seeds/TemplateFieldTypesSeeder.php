<?php

use Illuminate\Database\Seeder;

class TemplateFieldTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Empty the template_field_types table
        Schema::disableForeignKeyConstraints();

        DB::table('template_field_types')->delete();

        Schema::enableForeignKeyConstraints();

        $fieldTypes = json_decode(file_get_contents(__DIR__ . '/models/template_field_types.json'), true);

        collect($fieldTypes)->each(function ($type) {
            DB::table('template_field_types')->insert([
                'id' => (string) Uuid::generate(4),
                'title' => $type['title'],
                'name' => $type['name']
            ]);
        });
    }
}
