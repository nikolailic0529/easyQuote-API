<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateActiveVersionIdForeignKeyWorldwideQuotesTable extends Migration
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
        });

        Schema::table('worldwide_quotes', function (Blueprint $table) {
            $table->uuid('active_version_id')->nullable(false)->change();

            $table->foreign('active_version_id')->references('id')->on('worldwide_quote_versions')->restrictOnDelete()->cascadeOnUpdate();
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
        });

        Schema::table('worldwide_quotes', function (Blueprint $table) {
            $table->uuid('active_version_id')->nullable(true)->change();

            $table->foreign('active_version_id')->references('id')->on('worldwide_quote_versions')->nullOnDelete()->cascadeOnUpdate();
        });
    }
}
