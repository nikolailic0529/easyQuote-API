<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SystemSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('section')->index();

            $table->string('key');

            $table->text('value')->nullable();

            $table->string('type')->default('string');
            $table->string('label_format')->nullable();

            $table->json('possible_values')->nullable();
            $table->json('validation')->nullable();

            $table->boolean('is_read_only')->default(false);

            $table->unsignedTinyInteger('order')->default(1);

            $table->unique(['key', 'deleted_at']);

            $table->softDeletes()->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_settings');
    }
}
