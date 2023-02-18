<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeNullableWorldwideQuoteIdWorldwideQuoteNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quote_notes', function (Blueprint $table) {
            $table->dropForeign(['worldwide_quote_id']);
        });

        Schema::table('worldwide_quote_notes', function (Blueprint $table) {
            $table->uuid('worldwide_quote_id')->nullable(true)->change();
        });

        Schema::table('worldwide_quote_notes', function (Blueprint $table) {
            $table->foreign('worldwide_quote_id')->references('id')->on('worldwide_quotes')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worldwide_quote_notes', function (Blueprint $table) {
            $table->dropForeign(['worldwide_quote_id']);
        });

        Schema::table('worldwide_quote_notes', function (Blueprint $table) {
            $table->uuid('worldwide_quote_id')->nullable(false)->change();
        });

        Schema::table('worldwide_quote_notes', function (Blueprint $table) {
            $table->foreign('worldwide_quote_id')->references('id')->on('worldwide_quotes')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
}
