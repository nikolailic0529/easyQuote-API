<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReplicatedDistributionRowsGroupIdDistributionRowsGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('distribution_rows_groups', function (Blueprint $table) {
            $table->uuid('replicated_rows_group_id')
                ->nullable()
                ->after('id')
                ->index()
                ->comment('The rows group ID the group replicated from');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('distribution_rows_groups', function (Blueprint $table) {
            $table->dropIndex(['replicated_rows_group_id']);
            $table->dropColumn('replicated_rows_group_id');
        });
    }
}
