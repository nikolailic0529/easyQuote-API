<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDistributionCurrencyQuoteCurrencyExchangeRateColumnWorldwideDistributionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_distributions', function (Blueprint $table) {
            $table->decimal('distribution_currency_quote_currency_exchange_rate_value', 12, 4)->nullable()->comment('Exchange Rate between Distributor Quote Currency and Quote Currency');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worldwide_distributions', function (Blueprint $table) {
            $table->dropColumn('distribution_currency_quote_currency_exchange_rate_value');
        });
    }
}
