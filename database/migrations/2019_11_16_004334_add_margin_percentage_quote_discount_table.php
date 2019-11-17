<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMarginPercentageQuoteDiscountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quote_discount', function (Blueprint $table) {
            $table->decimal('margin_percentage')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quote_discount', function (Blueprint $table) {
            $table->dropColumn('margin_percentage');
        });
    }
}
