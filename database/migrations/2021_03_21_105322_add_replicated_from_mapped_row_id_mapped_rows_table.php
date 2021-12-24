<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReplicatedFromMappedRowIdMappedRowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mapped_rows', function (Blueprint $table) {
            $table->uuid('replicated_mapped_row_id')
                ->nullable()
                ->after('id')
                ->index()
                ->comment('The mapped row ID the row replicated from');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mapped_rows', function (Blueprint $table) {
            $table->dropIndex(['replicated_mapped_row_id']);

            $table->dropColumn('replicated_mapped_row_id');
        });
    }
}
