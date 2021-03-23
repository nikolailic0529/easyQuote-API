<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ContractTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $connection = $this->container['db.connection'];

        $connection->table('contract_types')
            ->insertOrIgnore(['id' => 'c4da2cab-7fd0-4f60-87df-2cc9ea602fee', 'type_name' => 'Fixed Package Service', 'type_short_name' => 'Pack']);

        $connection->table('contract_types')
            ->insertOrIgnore(['id' => 'c3c9d470-cb8b-48a2-9d3f-3614534b24a3', 'type_name' => 'Services Contract', 'type_short_name' => 'Contract']);
    }
}
