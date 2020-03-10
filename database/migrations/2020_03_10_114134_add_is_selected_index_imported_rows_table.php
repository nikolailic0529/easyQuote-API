<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsSelectedIndexImportedRowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('imported_rows', function (Blueprint $table) {
            $table->index(['deleted_at', 'is_selected']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('imported_rows', function (Blueprint $table) {
            $table->dropIndex(['deleted_at', 'is_selected']);
        });
    }
}
