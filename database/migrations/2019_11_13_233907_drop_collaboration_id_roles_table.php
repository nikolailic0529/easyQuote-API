<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropCollaborationIdRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['collaboration_id']);
            $table->dropColumn('collaboration_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->uuid('collaboration_id')->nullable();
            $table->foreign('collaboration_id')->references('id')->on('users')->onDelete('set null');
        });
    }
}
