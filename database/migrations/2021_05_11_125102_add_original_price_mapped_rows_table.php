<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddOriginalPriceMappedRowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mapped_rows', function (Blueprint $table) {
            $table->float('original_price', 16, 4)->after('price')->default(0.0)->comment('Original Price value');
        });

        DB::transaction(function () {

            DB::table('mapped_rows')
                ->update(['original_price' => DB::raw("coalesce((mapped_rows.price / (select worldwide_distributions.distribution_exchange_rate from worldwide_distributions where worldwide_distributions.distributor_file_id = mapped_rows.quote_file_id limit 1)), mapped_rows.price)")]);

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
            $table->dropColumn('original_price');
        });
    }
}
