<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModelNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('model_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('notification_key');
            $table->uuid('model_id');
            $table->string('model_type');

            $table->timestamps();

            $table->index(['notification_key', 'model_id', 'model_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('model_notifications');
    }
}
