<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentProcessLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('document_process_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('driver_id')->comment('Foreign key on document_processor_drivers table');

            $table->string('original_file_name', 250)->comment('Original file name');
            $table->string('file_type')->comment('File type');
            $table->string('file_path')->comment('File path in local filesystem');
            $table->string('comment', 500)->nullable()->comment('Process comment');

            $table->boolean('is_successful')->comment('Whether the process was successful');

            $table->timestamps();
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

        Schema::dropIfExists('document_process_logs');

        Schema::enableForeignKeyConstraints();
    }
}
