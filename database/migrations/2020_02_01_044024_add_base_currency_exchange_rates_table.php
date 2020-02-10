<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddBaseCurrencyExchangeRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->string('base_currency', 3)->after('currency_code');
            $table->index(['id', 'currency_id', 'base_currency']);
        });

        DB::table('exchange_rates')->update(['base_currency' => app('exchange.service')->baseCurrency()]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->dropIndex(['id', 'currency_id', 'base_currency']);
            $table->dropColumn('base_currency');
        });
    }
}
