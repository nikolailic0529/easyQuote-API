<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakePersonalRatingRenewalMonthAsStringOpportunitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->string('renewal_month')->nullable()->change();
            $table->string('personal_rating')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->unsignedSmallInteger('renewal_month')->nullable()->change();
            $table->unsignedSmallInteger('personal_rating')->nullable()->change();
        });
    }
}
