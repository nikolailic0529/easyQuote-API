<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUserIdColumnQuoteFilesTable extends Migration
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
            $table->uuid('user_id')->nullable(true)->change();
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
            $table->uuid('user_id')->nullable(false)->change();
        });
        Schema::enableForeignKeyConstraints();
    }
}
