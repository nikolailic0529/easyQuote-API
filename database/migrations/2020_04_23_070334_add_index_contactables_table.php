<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexContactablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contactables', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);

            $table->index(['contactable_id', 'contactable_type']);

            $table->primary(['contact_id', 'contactable_id', 'contactable_type']);
        });

        Schema::table('contactables', function (Blueprint $table) {
            $table->foreign('contact_id')->references('id')->on('contacts')->onUpdate('cascade')->onDelete('cascade');
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
            $table->dropForeign(['contact_id']);

            $table->dropIndex(['contactable_id', 'contactable_type']);

            $table->dropPrimary(['contact_id', 'contactable_id', 'contactable_type']);
        });

        Schema::table('contactables', function (Blueprint $table) {
            $table->foreign('contact_id')->references('id')->on('contacts')->onUpdate('cascade')->onDelete('cascade');
        });
    }
}
