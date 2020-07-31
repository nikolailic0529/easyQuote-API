<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReplicatedIdImportedRowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('imported_rows', function (Blueprint $table) {
            $table->uuid('replicated_row_id')->nullable()->index()->after('id')->comment('Replicated Row ID');
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
            $table->dropIndex(['replicated_row_id']);
            $table->dropColumn('replicated_row_id');
        });
    }
}
