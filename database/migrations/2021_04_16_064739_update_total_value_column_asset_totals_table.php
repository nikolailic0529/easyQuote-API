<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTotalValueColumnAssetTotalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('asset_totals', function (Blueprint $table) {
            $table->unsignedFloat('total_value')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('asset_totals', function (Blueprint $table) {
            $table->decimal('total_value', 15)->default(0)->change();
        });
    }
}
