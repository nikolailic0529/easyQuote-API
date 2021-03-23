<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CancelSalesOrderReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $connection = $this->container['db.connection'];

        $connection->transaction(function () use ($connection) {

            $connection->table('cancel_sales_order_reasons')
                ->insertOrIgnore([
                    'id' => '17d3c9c5-88f7-4277-abe8-71858dcc5fa6',
                    'description' => 'Duplicate Order',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

            $connection->table('cancel_sales_order_reasons')
                ->insertOrIgnore([
                    'id' => 'a6c46562-d572-4986-93f3-c8fcb58f8cf4',
                    'description' => 'Data Entry Mistake',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

            $connection->table('cancel_sales_order_reasons')
                ->insertOrIgnore([
                    'id' => '5d0852bb-bb61-4906-8321-2cde87887ab3',
                    'description' => 'Covid-19',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

            $connection->table('cancel_sales_order_reasons')
                ->insertOrIgnore([
                    'id' => '19b5d254-7d54-4ee0-8b78-c4555a44c1bc',
                    'description' => 'Payment Terms',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

        });
    }
}
