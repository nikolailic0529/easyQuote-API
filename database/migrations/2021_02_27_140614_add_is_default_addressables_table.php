<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsDefaultAddressablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('addressables', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->comment('Whether the address is default for the entity');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('addressables', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
}
