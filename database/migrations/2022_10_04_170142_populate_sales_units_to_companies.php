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
        $worldwideUnit = DB::table('sales_units')
            ->where('unit_name', 'Worldwide')
            ->whereNull('deleted_at')
            ->sole();

        DB::table('companies')
            ->whereNull('sales_unit_id')
            ->update(['sales_unit_id' => $worldwideUnit->id]);
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
