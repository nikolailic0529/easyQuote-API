<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeUserIdNullableWorldwideQuoteNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quote_notes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);

            $table->uuid('user_id')->nullable()->change();
        });

        Schema::table('worldwide_quote_notes', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete()->cascadeOnUpdate();
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
            $table->dropForeign(['user_id']);
        });

        Schema::table('worldwide_quote_notes', function (Blueprint $table) {
            $table->uuid('user_id')->nullable(false)->change();
        });

        Schema::table('worldwide_quote_notes', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
}
