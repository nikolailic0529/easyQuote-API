<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $seeds = DB::table('opportunities')
            ->select([
                'id', 'sale_unit_name',
            ])
            ->get()
            ->map(static function (object $opp): array {
                $unitId = DB::table('sales_units')
                    ->whereNull('deleted_at')
                    ->where('unit_name', $opp->sale_unit_name)
                    ->value('id');

                $unitId ??= '9e9ea3fc-e532-49f9-8b2b-e8bde016e149';

                return (array)$opp + ['sales_unit_id' => $unitId];
            });

        DB::transaction(static function () use ($seeds): void {
            foreach ($seeds as $seed) {
                DB::table('opportunities')
                    ->where('id', $seed['id'])
                    ->update(['sales_unit_id' => $seed['sales_unit_id']]);
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
        //
    }
};
