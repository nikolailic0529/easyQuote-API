<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuoteDiscountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quote_discount', function (Blueprint $table) {
            $table->uuid('quote_id');
            $table->foreign('quote_id')->references('id')->on('quotes');
            $table->uuid('discount_id');
            $table->foreign('discount_id')->references('id')->on('discounts');
            $table->unsignedTinyInteger('duration')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quote_discount');
    }
}
