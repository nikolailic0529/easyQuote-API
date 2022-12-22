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
        Schema::create('data_allocation_files', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('filepath', 500)->comment('Path to file in local filesystem');
            $table->string('filename', 500)->comment('Client original file name');
            $table->string('extension')->comment('File extension');
            $table->unsignedBigInteger('size')->default(0)->comment('File size in bytes');

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

        Schema::dropIfExists('data_allocation_files');

        Schema::enableForeignKeyConstraints();
    }
};
