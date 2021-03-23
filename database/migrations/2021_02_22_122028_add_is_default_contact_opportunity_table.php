<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsDefaultContactOpportunityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contact_opportunity', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->comment('Whether the contact is default');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contact_opportunity', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
}
