<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('data_allocation_user', function (Blueprint $table) {
            $table->unsignedBigInteger('entity_order')->default(0)->comment('Entity order');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('data_allocation_user', function (Blueprint $table) {
            $table->dropColumn('entity_order');
        });
    }
};
