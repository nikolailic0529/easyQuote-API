<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropPageUserIdQuoteFileIdImportedColumnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('imported_columns', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['quote_file_id']);

            $table->dropColumn([
                'user_id',
                'quote_file_id',
                'page'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('imported_columns', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('quote_file_id');
            $table->foreign('quote_file_id')->references('id')->on('quote_files')->onDelete('cascade');
            $table->tinyInteger('page')->nullable();
        });
    }
}
