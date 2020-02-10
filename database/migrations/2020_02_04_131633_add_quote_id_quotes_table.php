<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuoteIdQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->uuid('quote_id')->nullable()->after('customer_id');
            $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['quote_id']);
            $table->dropColumn('quote_id');
        });
    }
}
