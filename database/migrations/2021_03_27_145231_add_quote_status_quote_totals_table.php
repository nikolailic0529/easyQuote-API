<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuoteStatusQuoteTotalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('quote_totals')->truncate();

        Schema::table('quote_totals', function (Blueprint $table) {
            $table->mediumInteger('quote_status')->after('valid_until_date')->comment('Quote Status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quote_totals', function (Blueprint $table) {
            $table->dropColumn('quote_status');
        });
    }
}
