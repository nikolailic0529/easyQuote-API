<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TemplateFieldTypeSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     * @throws \Throwable
     */
    public function run()
    {
        $fieldTypes = json_decode(file_get_contents(__DIR__.'/models/template_field_types.json'), true);

        $connection = $this->container['db.connection'];

        $connection->transaction(function () use ($connection, $fieldTypes) {

            foreach ($fieldTypes as $fieldType) {

                $connection->table('template_field_types')
                    ->insertOrIgnore([
                        'id' => $fieldType['id'],
                        'title' => $fieldType['title'],
                        'name' => $fieldType['name']
                    ]);
            }

        });

    }
}
