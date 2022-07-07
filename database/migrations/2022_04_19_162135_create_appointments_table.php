<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('subject', 250)->comment('Subject of appointment');
            $table->text('description')->comment('Description of appointment');
            $table->dateTime('start_date')->comment('Start datetime of appointment');
            $table->dateTime('end_date')->comment('End datetime of appointment');
            $table->string('location')->default("")->comment('Location of appointment');
            $table->unsignedTinyInteger('flags')->default(0)->comment('Bitwise flags of appointment');

            $table->timestamps();
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
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('appointments');

        Schema::enableForeignKeyConstraints();
    }
};
