<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuoteFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quote_files', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id');
            $table->uuid('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('file_type')->nullable();
            $table->string('original_file_path');
            $table->string('current_file_path')->nullable();
            $table->integer('pages');
            $table->timestamps();
            $table->timestamp('drafted_at')->nullable()->default(null);
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
        Schema::dropIfExists('quote_files');
    }
}
