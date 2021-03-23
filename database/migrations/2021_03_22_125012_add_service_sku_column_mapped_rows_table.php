<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServiceSkuColumnMappedRowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mapped_rows', function (Blueprint $table) {
            $table->string('service_sku')->nullable()->after('product_no');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mapped_rows', function (Blueprint $table) {
            $table->dropColumn('service_sku');
        });
    }
}
