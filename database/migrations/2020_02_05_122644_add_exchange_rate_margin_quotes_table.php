<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExchangeRateMarginQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->decimal('exchange_rate_margin')->nullable()->after('buy_price');
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
            $table->dropColumn('exchange_rate_margin');
        });
    }
}
