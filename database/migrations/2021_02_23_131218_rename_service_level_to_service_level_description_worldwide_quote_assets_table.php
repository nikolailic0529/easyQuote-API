<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameServiceLevelToServiceLevelDescriptionWorldwideQuoteAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quote_assets', function (Blueprint $table) {
            $table->renameColumn('service_level', 'service_level_description');
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
            $table->renameColumn('service_level_description', 'service_level');
        });
    }
}
