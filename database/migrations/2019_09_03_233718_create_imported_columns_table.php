<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImportedColumnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('imported_columns', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id');
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->uuid('quote_file_id');
            $table->foreign('quote_file_id')->references('id')->on('quote_files');
            $table->uuid('importable_column_id')->nullable();
            $table->foreign('importable_column_id')->references('id')->on('importable_columns');
            $table->uuid('imported_row_id');
            $table->foreign('imported_row_id')->references('id')->on('imported_rows')->onDelete('cascade');
            $table->tinyInteger('page')->nullable();
            $table->string('value')->nullable();
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
        Schema::dropIfExists('imported_columns');
    }
}
