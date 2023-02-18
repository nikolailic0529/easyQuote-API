<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSortRowsColumnSortRowsDirectionWorldwideDistributionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_distributions', function (Blueprint $table) {
            $table->enum(
                'sort_rows_column',
                ['product_no', 'description', 'serial_no', 'date_from', 'date_to', 'qty', 'price', 'pricing_document', 'system_handle', 'service_level_description']
            )->nullable()->after('use_groups')->comment('Column name on the mapped_rows table for sorting');

            $table->enum(
                'sort_rows_direction',
                ['asc', 'desc']
            )->default('asc')->after('sort_rows_column')->comment('Sorting direction of the mapped rows');

            $table->enum(
                'sort_rows_groups_column',
                ['group_name', 'search_text', 'rows_count', 'rows_sum']
            )->nullable()->after('sort_rows_direction')->comment('Column name on the distribution_rows_groups table for sorting');

            $table->enum(
                'sort_rows_groups_direction',
                ['asc', 'desc']
            )->default('asc')->after('sort_rows_groups_column')->comment('Sorting direction of the distribution groups');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worldwide_distributions', function (Blueprint $table) {
            $table->dropColumn([
                'sort_rows_column',
                'sort_rows_direction',
                'sort_rows_groups_column',
                'sort_rows_groups_direction',
            ]);
        });
    }
}
