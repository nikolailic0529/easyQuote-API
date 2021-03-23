<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsDefaultAddressOpportunityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('address_opportunity', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->comment('Whether the address is default');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('address_opportunity', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
}
