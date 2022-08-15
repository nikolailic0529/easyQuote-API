<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Webpatser\Uuid\Uuid;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run()
    {
        $seeds = json_decode(file_get_contents(database_path('seeders/models/system_settings.json')), true);

        $seeds = collect($seeds)
            ->groupBy('section')
            ->map(function (Collection $section) {

                return $section->map(function (array $seed, int $key) {
                    return [
                            'order' => $key + 1,
                            'value' => $this->resolveSettingValue($seed['key'], $seed['value']),
                            'possible_values' => $this->resolvePossibleValues($seed['possible_values'] ?? null)
                        ] + $seed;
                })
                    ->all();

            })
            ->collapse()
            ->all();

        /** @var \Illuminate\Database\ConnectionInterface $connection */
        $connection = $this->container['db.connection'];

        foreach ($seeds as $seed) {

            $connection
                ->table('system_settings')
                ->upsert([
                    'id' => (string)Uuid::generate(4),
                    'key' => $seed['key'],
                    'section' => $seed['section'],
                    'value' => $seed['value'],
                    'type' => $seed['type'] ?? 'string',
                    'possible_values' => $seed['possible_values'],
                    'validation' => transform($seed['validation'] ?? null, function (array $validation) {
                        return json_encode($validation);
                    }),
                    'is_read_only' => $seed['is_read_only'] ?? false,
                    'label_format' => $seed['label_format'] ?? null,
                ], 'key', [
                    'section' => $seed['section'],
                    'type' => $seed['type'] ?? 'string',
                    'possible_values' => $seed['possible_values'],
                    'validation' => transform($seed['validation'] ?? null, function (array $validation) {
                        return json_encode($validation);
                    }),
                    'is_read_only' => $seed['is_read_only'] ?? false,
                    'label_format' => $seed['label_format'] ?? null,
                ]);

        }
    }

    protected function resolveSettingValue(string $key, $value)
    {
        if (is_array($value)) {
            return json_encode($value, true);
        }

        if ($key === 'failure_report_recipients') {

            return $this->container['db.connection']
                ->table('users')
                ->whereNull('deleted_at')
                ->whereIn('email', $value)
                ->pluck('id')
                ->all();
        }

        return $value;
    }

    protected function resolvePossibleValues($values)
    {
        if (is_null($values)) {
            return null;
        }

        if (is_string($values) && Str::containsAll($values, ['RANGE:', ',', '{value}'])) {

            $range = Str::after($values, 'RANGE:');
            $parameters = explode(',', $range);

            if (count($parameters) < 3) {
                return $values;
            }

            $from = (int)$parameters[0];
            $to = (int)$parameters[1];

            [$label, $even] = value(function () use ($parameters) {

                if (count($parameters) > 3) {

                    return [$parameters[3], $parameters[2]];

                }

                return [$parameters[2], null];

            });

            $values = [];

            for ($i = $from; $i <= $to; $i++) {

                if (!is_null($even) && ($i % $even) !== 0) {
                    continue;
                }

                array_push($values, [
                    'label' => str_replace('{value}', $i, $label),
                    'value' => $i
                ]);
            }

            return json_encode($values);
        }

        return json_encode($values);
    }
}
