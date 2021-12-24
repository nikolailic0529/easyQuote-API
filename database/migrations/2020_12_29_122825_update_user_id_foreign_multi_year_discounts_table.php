<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUserIdForeignMultiYearDiscountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('multi_year_discounts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('multi_year_discounts', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->change();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('multi_year_discounts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('multi_year_discounts', function (Blueprint $table) {
            $table->uuid('user_id')->nullable(false)->change();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::enableForeignKeyConstraints();
    }
}
