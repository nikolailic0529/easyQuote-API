<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateActiveVersionIdForeignKeyConstraintsWorldwideQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quotes', function (Blueprint $table) {
            $table->dropForeign(['active_version_id']);

            $table->foreign('active_version_id')->references('id')->on('worldwide_quote_versions')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worldwide_quotes', function (Blueprint $table) {
            $table->dropForeign(['active_version_id']);

            $table->foreign('active_version_id')->references('id')->on('worldwide_quote_versions')->restrictOnDelete()->cascadeOnUpdate();
        });
    }
}
