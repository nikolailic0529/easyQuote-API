<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('email');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->string('password');
            $table->rememberToken();

            $table->string('ip_address')->nullable();
            $table->string('default_route')->nullable();
            
            $table->boolean('already_logged_in')->default(false);
            
            $table->unsignedInteger('recent_notifications_limit')->default(10);
            $table->unsignedInteger('failed_attempts')->index()->default(0)->comment('Failed access attempts');

            $table->timestamps();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->timestamp('activated_at')->index()->nullable();
            $table->softDeletes()->index();

            $table->unique(['email', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
