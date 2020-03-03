<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddColumnsDataImportedRowsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('imported_rows', function (Blueprint $table) {
            $table->json('columns_data')->nullable()->after('quote_file_id');
        });

        $query = DB::connection('mysql_unbuffered')->table('imported_rows')->whereNull('deleted_at');

        $importedRows = $query->cursor();

        $importedRows->chunk(1000)->each->each(function ($row) {
            $columns_data = DB::table('imported_columns')->where('imported_row_id', $row->id)->get(['header', 'value', 'importable_column_id'])->keyBy('importable_column_id')->toJson();
            DB::table('imported_rows')->where('id', $row->id)->update(compact('columns_data'));
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
            $table->dropColumn('columns_data');
        });
    }
}
