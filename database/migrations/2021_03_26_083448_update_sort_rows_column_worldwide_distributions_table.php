<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSortRowsColumnWorldwideDistributionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_distributions', function (Blueprint $table) {
            $table->dropColumn('sort_rows_column');
        });

        Schema::table('worldwide_distributions', function (Blueprint $table) {
            $table->enum('sort_rows_column', ['product_no','service_sku','description','serial_no','date_from','date_to','qty','price','pricing_document','system_handle','service_level_description','machine_address'])
                ->nullable()
                ->after('use_groups')
                ->comment('Column name on the mapped_rows table for sorting');
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
            $table->dropColumn('sort_rows_column');
        });

        Schema::table('worldwide_distributions', function (Blueprint $table) {
            $table->enum('sort_rows_column', ['product_no','description','serial_no','date_from','date_to','qty','price','pricing_document','system_handle','service_level_description'])
                ->nullable()
                ->after('use_groups')
                ->comment('Column name on the mapped_rows table for sorting');
        });
    }
}
