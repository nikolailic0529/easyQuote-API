<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTotalValueColumnCustomerTotalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_totals', function (Blueprint $table) {
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
        Schema::table('customer_totals', function (Blueprint $table) {
            $table->decimal('total_value', 15)->default(0)->change();
        });
    }
}
