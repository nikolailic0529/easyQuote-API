<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class TemplateFieldsSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run()
    {
        $seeds = json_decode(file_get_contents(__DIR__ . '/models/template_fields.json'), true);

        $types = DB::table('template_field_types')->whereIn('name', array_unique(Arr::pluck($seeds, 'type')))->pluck('id', 'name');

        $seeds = array_map(function (array $attributes) use ($types) {
            return [
                'id' => $attributes['id'],
                'header' => $attributes['header'],
                'name' => $attributes['name'],
                'is_required' => $attributes['is_required'] ?? false,
                'is_system' => true,
                'order' => $attributes['order'],
                'template_field_type_id' => $types[$attributes['type']]
            ];
        }, $seeds);

        DB::beginTransaction();

        try {
            DB::table('template_fields')->insertOrIgnore(
                $seeds
            );

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }
}
