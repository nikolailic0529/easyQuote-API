<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
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
                    'id' => (string) Uuid::generate(4),
                    'key' => $seed['key'],
                    'section' => $seed['section'],
                    'value' => $seed['value'],
                    'type' => $seed['type'] ?? 'string',
                    'field_type' => $seed['field_type'],
                    'validation' => transform($seed['validation'] ?? null, function (array $validation) {
                        return json_encode($validation);
                    }),
                    'is_read_only' => $seed['is_read_only'] ?? false,
                    'label_format' => $seed['label_format'] ?? null,
                    'order' => $seed['order'],
                ], 'key', [
                    'section' => $seed['section'],
                    'type' => $seed['type'] ?? 'string',
                    'field_type' => $seed['field_type'],
                    'validation' => transform($seed['validation'] ?? null, function (array $validation) {
                        return json_encode($validation);
                    }),
                    'is_read_only' => $seed['is_read_only'] ?? false,
                    'label_format' => $seed['label_format'] ?? null,
                    'order' => $seed['order'],
                ]);
        }
    }

    protected function resolveSettingValue(string $key, $value)
    {
        if ('failure_report_recipients' === $key) {
            return $this->container['db.connection']
                ->table('users')
                ->whereNull('deleted_at')
                ->whereIn('email', Arr::wrap($value))
                ->pluck('id')
                ->toJson();
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
