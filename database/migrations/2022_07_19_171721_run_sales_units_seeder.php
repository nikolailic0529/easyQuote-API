<?php

use Illuminate\Database\Migrations\Migration;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $seed = [
            'id' => '9e9ea3fc-e532-49f9-8b2b-e8bde016e149',
            'pl_reference' => '79ed86c3-d96b-4b6d-8fb0-f887602a5f28',
            'unit_name' => 'Worldwide',
            'is_default' => 0,
            'entity_order' => 1,
        ];

        \Illuminate\Support\Facades\DB::table('sales_units')
            ->insertOrIgnore($seed);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
