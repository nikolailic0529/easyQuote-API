<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameExchangeRateMarginToDistributionCurrencyQuoteCurrencyExchangeRateMarginWorldwideDistributionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_distributions', function (Blueprint $table) {
            $table->renameColumn('exchange_rate_margin', 'distribution_currency_quote_currency_exchange_rate_margin');
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
            $table->renameColumn('distribution_currency_quote_currency_exchange_rate_margin', 'exchange_rate_margin');
        });
    }
}
