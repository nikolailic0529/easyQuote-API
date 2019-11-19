<?php

use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Empty the system_settings table
        Schema::disableForeignKeyConstraints();

        DB::table('system_settings')->delete();

        Schema::enableForeignKeyConstraints();

        $settings = json_decode(file_get_contents(__DIR__ . '/models/system_settings.json'), true);

        collect($settings)->each(function ($setting) {
            DB::table('system_settings')->insert([
                'id' => (string) Uuid::generate(4),
                'key' => $setting['key'],
                'value' => is_array($setting['value']) ? json_encode($setting['value'], true) : $setting['value'],
                'type' => $setting['type'] ?? 'string',
                'possible_values' => isset($setting['possible_values']) ? json_encode($setting['possible_values'], true) : null,
                'is_read_only' => $setting['is_read_only'] ?? false,
                'label_format' => $setting['label_format'] ?? null
            ]);
        });
    }
}
