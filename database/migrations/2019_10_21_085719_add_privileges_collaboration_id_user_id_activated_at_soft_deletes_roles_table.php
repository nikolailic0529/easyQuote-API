<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPrivilegesCollaborationIdUserIdActivatedAtSoftDeletesRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->json('privileges')->nullable();
            $table->uuid('collaboration_id')->nullable();
            $table->foreign('collaboration_id')->references('id')->on('users')->onDelete('set null');
            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('activated_at')->nullable();
            $table->softDeletes();
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
            $table->dropForeign(['collaboration_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(
                [
                    'privileges',
                    'collaboration_id',
                    'user_id',
                    'activated_at'
                ]
            );
            $table->dropSoftDeletes();
        });
    }
}
