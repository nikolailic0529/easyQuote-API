<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBuyCurrencyIdWorldwideQuoteAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quote_assets', function (Blueprint $table) {
            $table->foreignUuid('buy_currency_id')->nullable()->after('machine_address_id')->comment('Foreign key on currencies table')->constrained('currencies')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('worldwide_quote_assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('buy_currency_id');
        });

        Schema::enableForeignKeyConstraints();
    }
}
