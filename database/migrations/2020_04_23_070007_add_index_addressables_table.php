<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexAddressablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('addressables', function (Blueprint $table) {
            $table->dropForeign(['address_id']);

            $table->index(['addressable_id', 'addressable_type']);

            $table->primary(['address_id', 'addressable_id', 'addressable_type']);
        });

        Schema::table('addressables', function (Blueprint $table) {
            $table->foreign('address_id')->references('id')->on('addresses')->onUpdate('cascade')->onDelete('cascade');
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
            $table->dropForeign(['address_id']);

            $table->dropIndex(['addressable_id', 'addressable_type']);

            $table->dropPrimary(['address_id', 'addressable_id', 'addressable_type']);
        });

        Schema::table('addressables', function (Blueprint $table) {
            $table->foreign('address_id')->references('id')->on('addresses')->onUpdate('cascade')->onDelete('cascade');
        });
    }
}
