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
            $table->uuid('id')->primary();

            $table->uuid('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->string('file_type')->index()->nullable();
            $table->string('original_file_path');
            $table->string('original_file_name')->nullable();
            
            $table->integer('pages');
            $table->tinyInteger('imported_page')->nullable();

            $table->json('meta_attributes')->nullable();

            $table->timestamps();
            $table->timestamp('drafted_at')->nullable()->default(null);
            $table->timestamp('automapped_at')->nullable();
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
        Schema::dropIfExists('quote_files');
    }
}
