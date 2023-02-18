<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales_units', function (Blueprint $table) {
            $table->boolean('is_default')->default(0)->after('unit_name')->comment('Whether the entity is default');

            $table->unique([DB::raw('(IF(is_default = true, true, null))'), DB::raw('(IF(deleted_at is null, 1, null))')], 'sales_units_is_default_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales_units', function (Blueprint $table) {
            $table->dropUnique('sales_units_is_default_unique');
            $table->dropColumn('is_default');
        });
    }
};
