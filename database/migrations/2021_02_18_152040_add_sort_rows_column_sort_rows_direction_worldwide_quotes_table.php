<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSortRowsColumnSortRowsDirectionWorldwideQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quotes', function (Blueprint $table) {
            $table->enum('sort_rows_column', ['sku', 'serial_no', 'product_name', 'expiry_date', 'price', 'service_level_description', 'vendor_short_code'])
                ->nullable()
                ->after('margin_method')
                ->comment('Column name on the worldwide_quote_assets table for sorting');

            $table->enum('sort_rows_direction', ['asc', 'desc'])
                ->default('asc')
                ->after('sort_rows_column')
                ->comment('Sorting direction of the pack assets');


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worldwide_quotes', function (Blueprint $table) {
            $table->dropColumn([
                'sort_rows_column',
                'sort_rows_direction'
            ]);
        });
    }
}
