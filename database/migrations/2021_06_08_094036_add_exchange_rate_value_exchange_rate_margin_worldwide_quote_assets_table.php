<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExchangeRateValueExchangeRateMarginWorldwideQuoteAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quote_assets', function (Blueprint $table) {
            $table->decimal('exchange_rate_value', 12, 4)->nullable()->after('original_price')->comment('Exchange Rate Value of Asset');
            $table->decimal('exchange_rate_margin', 12, 4)->nullable()->after('exchange_rate_value')->comment('Exchange Rate Margin of Asset');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worldwide_quote_assets', function (Blueprint $table) {
            $table->dropColumn([
                'exchange_rate_value',
                'exchange_rate_margin'
            ]);
        });
    }
}
