<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $userIds = DB::table('users')
            ->pluck('id');

        DB::transaction(static function () use ($userIds): void {
            foreach ($userIds as $id) {
                DB::table('model_has_sales_units')
                    ->insertOrIgnore(['model_id' => $id, 'model_type' => '7209618c-9c1a-4b52-ba1d-933b3a433b3c',
                        'sales_unit_id' => '9e9ea3fc-e532-49f9-8b2b-e8bde016e149']);
            }
        });
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
