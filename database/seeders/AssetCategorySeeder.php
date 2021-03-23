<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AssetCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $assetCategories = json_decode(file_get_contents(database_path('seeders/models/asset_categories.json')), true);

        $connection = $this->container['db.connection'];

        $connection->transaction(function () use ($connection, $assetCategories) {

            foreach ($assetCategories as $assetCategory) {

                $connection->table('asset_categories')
                    ->insertOrIgnore([
                        'id' => $assetCategory['id'],
                        'name' => $assetCategory['name']
                    ]);

            }

        });
    }
}
