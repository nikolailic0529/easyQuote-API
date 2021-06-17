<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUseGroupsWorldwideQuoteVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quote_versions', function (Blueprint $table) {
            $table->boolean('use_groups')->default(0)->after('buy_price')->comment('Whether use or not grouped assets');
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
            $table->dropColumn('use_groups');
        });
    }
}
