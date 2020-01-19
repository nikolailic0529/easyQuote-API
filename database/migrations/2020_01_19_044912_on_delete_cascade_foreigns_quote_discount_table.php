<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class OnDeleteCascadeForeignsQuoteDiscountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quote_discount', function (Blueprint $table) {
            $table->dropForeign(['quote_id']);
            $table->dropForeign(['discount_id']);
        });

        Schema::table('quote_discount', function (Blueprint $table) {
            $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('cascade');
            $table->foreign('discount_id')->references('id')->on('discounts')->onDelete('cascade');
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
            $table->dropForeign(['quote_id']);
            $table->dropForeign(['discount_id']);
        });

        Schema::table('quote_discount', function (Blueprint $table) {
            $table->foreign('quote_id')->references('id')->on('quotes');
            $table->foreign('discount_id')->references('id')->on('discounts');
        });
    }
}
