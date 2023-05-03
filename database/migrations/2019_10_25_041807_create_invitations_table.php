<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvitationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->uuid('role_id');
            $table->foreign('role_id')->references('id')->on('roles');

            $table->string('email');
            $table->string('invitation_token');
            $table->string('host')->nullable();

            $table->timestamps();
            $table->timestamp('expires_at')->nullable();
            $table->softDeletes()->index();

            $table->unique(['email', 'invitation_token']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invitations');
    }
}
