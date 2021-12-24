<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuoteTypeQuoteTotalsTable extends Migration
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
            $table->uuid('quote_type')->after('quote_id')->comment('Quote Morph Type');

            $table->index(['quote_type', 'quote_id']);
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
            $table->dropIndex(['quote_type', 'quote_id']);

            $table->dropColumn('quote_type');
        });
    }
}
