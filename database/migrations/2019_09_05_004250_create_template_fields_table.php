<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTemplateFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('template_fields', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id');
            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('header', 50);
            $table->string('name', 20);
            $table->tinyInteger('order');
            $table->tinyInteger('cols')->default(12);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_column')->default(false);
            $table->timestamps();
            $table->timestamp('drafted_at')->nullable();
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
        Schema::dropIfExists('template_fields');
    }
}
