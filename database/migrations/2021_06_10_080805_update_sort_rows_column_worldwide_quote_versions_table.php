<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateSortRowsColumnWorldwideQuoteVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quote_versions', function (Blueprint $table) {
            $table->dropColumn('sort_rows_column');
        });

        Schema::table('worldwide_quote_versions', function (Blueprint $table) {
            $table->enum('sort_rows_column', ['sku','serial_no','product_name','expiry_date','price','service_level_description','vendor_short_code','machine_address', 'buy_currency_code'])
                ->nullable()
                ->after('use_groups')
                ->comment('Column name on the worldwide_quote_assets table for sorting');
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
            $table->dropColumn('sort_rows_column');
        });

        Schema::table('worldwide_quote_versions', function (Blueprint $table) {
            $table->enum('sort_rows_column', ['sku','serial_no','product_name','expiry_date','price','service_level_description','vendor_short_code','machine_address'])
                ->nullable()
                ->after('use_groups')
                ->comment('Column name on the worldwide_quote_assets table for sorting');
        });
    }
}
