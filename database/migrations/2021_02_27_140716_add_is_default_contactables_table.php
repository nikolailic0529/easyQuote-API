<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsDefaultContactablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contactables', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->comment('Whether the contact is default for the entity');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contactables', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
}
