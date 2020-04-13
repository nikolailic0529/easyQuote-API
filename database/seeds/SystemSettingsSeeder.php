<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
            $key = $setting['key'];
            $value = $setting['value'];
            $possibleValues = $this->formatPossibleValues(
                optional($setting)['possible_values']
            );

            DB::table('system_settings')->insert([
                'id' => (string) Uuid::generate(4),
                'key' => $key,
                'value' => $this->formatValue($key, $value),
                'type' => $setting['type'] ?? 'string',
                'section' => $setting['section'],
                'possible_values' => $possibleValues,
                'is_read_only' => $setting['is_read_only'] ?? false,
                'label_format' => $setting['label_format'] ?? null
            ]);
        });
    }

    protected function formatValue(string $key, $value)
    {
        if (is_array($value)) {
            return json_encode($value, true);
        }

        if ($key === 'failure_report_recipients') {
            return app('user.repository')->findByEmail($value)->pluck('id')->toArray();
        }

        if (is_string($value) && Str::contains($value, 'CONST:')) {
            return constant(Str::after($value, 'CONST:'));
        }

        return $value;
    }

    protected function formatPossibleValues($values)
    {
        if (is_string($values) && Str::containsAll($values, ['RANGE:', ',', '{value}'])) {
            $range = Str::after($values, 'RANGE:');
            $parameters = explode(',', $range);

            if (count($parameters) < 3) {
                return $values;
            }

            $from = (int) $parameters[0];
            $to = (int) $parameters[1];
            $label = $parameters[2];

            $values = [];

            for ($i = $from; $i <= $to; $i++) {
                array_push($values, [
                    'label' => str_replace('{value}', $i, $label),
                    'value' => $i
                ]);
            }

            return json_encode($values);
        }

        if (!is_null($values)) {
            return json_encode($values);
        }

        return $values;
    }
}
