<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSortAssetsGroupsColumnSortAssetsGroupsDirectionColumnsWorldwideQuoteVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quote_versions', function (Blueprint $table) {
            $table->enum('sort_assets_groups_column', ['group_name', 'search_text', 'assets_count', 'assets_sum'])->nullable()->after('sort_rows_column')->comment('Column name on the assets group entity for sorting');
            $table->enum('sort_assets_groups_direction', ['asc', 'desc'])->default('asc')->after('sort_assets_groups_column')->comment('Sorting direction of the assets groups');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worldwide_quote_versions', function (Blueprint $table) {
            $table->dropColumn([
                'sort_assets_groups_column',
                'sort_assets_groups_direction',
            ]);
        });
    }
}
