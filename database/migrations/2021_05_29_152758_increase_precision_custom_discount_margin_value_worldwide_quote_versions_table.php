<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class IncreasePrecisionCustomDiscountMarginValueWorldwideQuoteVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quote_versions', function (Blueprint $table) {
            $table->decimal('custom_discount', 8, 4)->change();
            $table->decimal('margin_value', 8, 4)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worldwide_quote_versions', function (Blueprint $table) {
            $table->decimal('custom_discount')->change();
            $table->decimal('margin_value')->change();
        });
    }
}
