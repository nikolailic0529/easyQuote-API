<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quote_assets', function (Blueprint $table) {
            $table->bigInteger('entity_order')->default(0)->after('exchange_rate_margin')->comment('Entity order');
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
            $table->dropColumn('entity_order');
        });
    }
};
