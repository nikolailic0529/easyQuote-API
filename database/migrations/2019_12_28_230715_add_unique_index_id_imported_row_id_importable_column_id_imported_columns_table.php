<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueIndexIdImportedRowIdImportableColumnIdImportedColumnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('imported_columns', function (Blueprint $table) {
            $table->unique(['id', 'imported_row_id', 'importable_column_id']);
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
            $table->dropUnique(['id', 'imported_row_id', 'importable_column_id']);
        });
    }
}
