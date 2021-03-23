<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuoteNumberQuoteNumberSequenceWorldwideQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quotes', function (Blueprint $table) {
            $table->string('quote_number')->after('output_currency_id')->comment('Quotation number');
            $table->unsignedBigInteger('sequence_number')->unique()->after('quote_number')->comment('Unique Quotation Sequence number');
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
            $table->dropUnique(['sequence_number']);

            $table->dropColumn('quote_number');
            $table->dropColumn('sequence_number');
        });
    }
}
