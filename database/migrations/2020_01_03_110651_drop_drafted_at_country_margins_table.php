<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropDraftedAtCountryMarginsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('country_margins', function (Blueprint $table) {
            $table->dropColumn('drafted_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('country_margins', function (Blueprint $table) {
            $table->timestamp('drafted_at')->nullable();
        });
    }
}
