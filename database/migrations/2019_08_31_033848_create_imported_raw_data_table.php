<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImportedRawDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('imported_raw_data', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id');
            // $table->uuid('user_id');
            // $table->foreign('user_id')->references('id')->on('users');
            $table->uuid('quote_file_id');
            $table->foreign('quote_file_id')->references('id')->on('quote_files');
            $table->integer('page');
            $table->text('content');
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
        Schema::dropIfExists('imported_raw_data');
    }
}
