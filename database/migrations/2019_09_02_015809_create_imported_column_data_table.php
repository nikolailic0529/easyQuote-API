<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImportedColumnDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('imported_column_data', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id');
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->uuid('quote_file_id');
            $table->foreign('quote_file_id')->references('id')->on('quote_files');
            $table->uuid('importable_column_id');
            $table->foreign('importable_column_id')->references('id')->on('importable_columns');
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
        Schema::dropIfExists('imported_column_data');
    }
}
