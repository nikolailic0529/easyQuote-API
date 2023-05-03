<?php

namespace Database\Seeders;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Webpatser\Uuid\Uuid;

class ImportableColumnSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function run()
    {
        $seeds = yaml_parse_file(database_path('seeders/models/importable_columns.yaml'));

        /** @var ConnectionInterface $connection */
        $connection = $this->container['db.connection'];

        $seeds = array_map(function (array $seed) use ($connection) {
            $country = $connection
                ->table('countries')
                ->where('iso_3166_2', Arr::pull($seed, 'country'))
                ->whereNull('deleted_at')
                ->sole(['id']);

            $existingAliases = $connection->table('importable_column_aliases')
                ->where('importable_column_id', $seed['id'])
                ->whereIn('alias', $seed['aliases'])
                ->whereNull('deleted_at')
                ->pluck('alias')
                ->all();

            $missingAliases = array_values(array_diff($seed['aliases'], $existingAliases));

            $aliases = array_map(function (string $aliasName) {
                return [
                    'id' => (string) Uuid::generate(4),
                    'alias' => $aliasName,
                ];
            }, $missingAliases);

            return array_merge($seed, [
                'country_id' => $country->id,
                'aliases' => $aliases,
            ]);
        }, $seeds);

        $connection->transaction(function () use ($seeds, $connection) {
            foreach ($seeds as $seed) {
                $connection->table('importable_columns')
                    ->insertOrIgnore([
                        'id' => $seed['id'],
                        'de_header_reference' => $seed['de_header_reference'],
                        'header' => $seed['header'],
                        'name' => $seed['name'],
                        'order' => $seed['order'],
                        'type' => $seed['type'],
                        'is_system' => true,
                        'is_temp' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'activated_at' => now(),
                    ]);

                foreach ($seed['aliases'] as $aliasData) {
                    $connection->table('importable_column_aliases')
                        ->insertOrIgnore([
                            'id' => $aliasData['id'],
                            'importable_column_id' => $seed['id'],
                            'alias' => $aliasData['alias'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }
        });
    }
}
