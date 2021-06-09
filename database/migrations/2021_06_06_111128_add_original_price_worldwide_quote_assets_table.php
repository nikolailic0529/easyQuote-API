<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOriginalPriceWorldwideQuoteAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quote_assets', function (Blueprint $table) {
            $table->decimal('original_price', 16, 4)->nullable()->default(0.0)->after('price')->comment('Original Price value');
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
            $table->dropColumn('original_price');
        });
    }
}
