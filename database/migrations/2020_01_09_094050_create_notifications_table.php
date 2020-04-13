<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->uuid('subject_id')->nullable();
            $table->string('subject_type')->nullable();

            $table->string('message');
            $table->string('url')->nullable();

            $table->tinyInteger('priority')->default(1);

            $table->timestamps();
            $table->timestamp('read_at')->index()->nullable();
            $table->softDeletes()->index();

            $table->index(['subject_id', 'subject_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
}
