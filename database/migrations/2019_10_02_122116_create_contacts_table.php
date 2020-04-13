<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('image_id')->nullable();
            $table->foreign('image_id')->references('id')->on('images')->onDelete('set null');

            $table->string('contact_type')->nullable();
            $table->string('contact_name')->nullable();

            $table->string('email')->nullable();

            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();

            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();

            $table->string('job_title')->nullable();

            $table->boolean('is_verified')->default(false);

            $table->timestamps();
            $table->timestamp('activated_at')->index()->nullable();
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
        Schema::dropIfExists('contacts');
    }
}
