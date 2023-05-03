<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddBuyCurrencyIdWorldwideQuoteVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quote_versions', function (Blueprint $table) {
            $table->foreignUuid('buy_currency_id')->nullable()->after('quote_currency_id')->comment('Foreign key on currencies table')->constrained('currencies')->cascadeOnDelete()->cascadeOnUpdate();
        });

        DB::transaction(function () {
            DB::table('worldwide_quote_versions')
                ->update([
                    'buy_currency_id' => DB::raw('quote_currency_id'),
                ]);
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
            $table->dropConstrainedForeignId('buy_currency_id');
        });
    }
}
