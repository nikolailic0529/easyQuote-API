<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsSelectedDistributionRowsGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('distribution_rows_groups', function (Blueprint $table) {
            $table->boolean('is_selected')->default(false)->after('search_text')->comment('Whether the group is selected');
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
            $table->dropColumn('is_selected');
        });
    }
}
