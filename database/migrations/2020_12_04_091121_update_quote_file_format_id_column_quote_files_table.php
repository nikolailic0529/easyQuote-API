<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateQuoteFileFormatIdColumnQuoteFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('quote_files', function (Blueprint $table) {
            $table->dropForeign(['quote_file_format_id']);
        });

        Schema::table('quote_files', function (Blueprint $table) {
            $table->uuid('quote_file_format_id')->nullable(true)->change();
            $table->foreign('quote_file_format_id')->references('id')->on('quote_file_formats')->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('quote_files', function (Blueprint $table) {
            $table->dropForeign(['quote_file_format_id']);
        });

        Schema::table('quote_files', function (Blueprint $table) {
            $table->uuid('quote_file_format_id')->nullable(false)->change();
            $table->foreign('quote_file_format_id')->references('id')->on('quote_file_formats')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::enableForeignKeyConstraints();
    }
}
