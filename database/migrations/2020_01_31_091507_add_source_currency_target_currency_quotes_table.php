<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSourceCurrencyTargetCurrencyQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->uuid('source_currency_id')->nullable();
            $table->foreign('source_currency_id')->references('id')->on('currencies')->onDelete('set null');
            $table->uuid('target_currency_id')->nullable();
            $table->foreign('target_currency_id')->references('id')->on('currencies')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['source_currency_id']);
            $table->dropForeign(['target_currency_id']);
            $table->dropColumn(['source_currency_id', 'target_currency_id']);
        });
    }
}
