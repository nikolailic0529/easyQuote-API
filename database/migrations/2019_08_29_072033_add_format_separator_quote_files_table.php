<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFormatSeparatorQuoteFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quote_files', function (Blueprint $table) {
            $table->uuid('quote_file_format_id');
            $table->foreign('quote_file_format_id')->references('id')->on('quote_file_formats')->onDelete('cascade');
            $table->uuid('data_select_separator_id')->nullable();
            $table->foreign('data_select_separator_id')->references('id')->on('data_select_separators')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quote_files', function (Blueprint $table) {
            $table->dropForeign(['quote_file_format_id']);
            $table->dropForeign(['data_select_separator_id']);
        });
    }
}
