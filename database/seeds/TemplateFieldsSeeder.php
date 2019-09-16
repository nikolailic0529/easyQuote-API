<?php

use Illuminate\Database\Seeder;
use App\Models\QuoteTemplate\TemplateFieldType;

class TemplateFieldsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Empty the template_fields table
        Schema::disableForeignKeyConstraints();

        DB::table('template_fields')->delete();

        Schema::enableForeignKeyConstraints();

        $templateFields = json_decode(file_get_contents(__DIR__ . '/models/template_fields.json'), true);


        collect($templateFields)->each(function ($field) {
            $defaultValue = null;

            $typeId = TemplateFieldType::whereName($field['type'])->first()->id;

            if(isset($field['default_value']) && $field['default_value'] === 'current_date') {
                $defaultValue = now()->format('d/m/Y');
            }

            DB::table('template_fields')->insert([
                'id' => (string) Uuid::generate(4),
                'header' => $field['header'],
                'name' => $field['name'],
                'is_required' => isset($field['is_required']) ? $field['is_required'] : false,
                'default_value' => $defaultValue ?: null,
                'is_system' => true,
                'is_column' => true,
                'order' => $field['order'],
                'template_field_type_id' => $typeId
            ]);
        });
    }
}
