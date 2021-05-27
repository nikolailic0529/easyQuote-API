<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddBuyCurrencyIdWorldwideDistributionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_distributions', function (Blueprint $table) {
            $table->foreignUuid('buy_currency_id')->nullable()->after('distribution_currency_id')->comment('Foreign key on currencies table')->constrained('currencies')->cascadeOnDelete()->cascadeOnUpdate();
        });

        DB::transaction(function () {

            DB::table('worldwide_distributions')
                ->update([
                    'buy_currency_id' => DB::raw('distribution_currency_id')
                ]);

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
            $table->dropConstrainedForeignId('buy_currency_id');
        });
    }
}
