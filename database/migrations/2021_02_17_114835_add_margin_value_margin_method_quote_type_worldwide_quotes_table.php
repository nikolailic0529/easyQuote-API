<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMarginValueMarginMethodQuoteTypeWorldwideQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quotes', function (Blueprint $table) {
            $table->string('quote_type')->nullable()->after('sequence_number')->comment('Pack Quote Type (New, Renewal)');
            $table->float('margin_value')->nullable()->after('quote_type')->comment('Pack Quote Margin Value');
            $table->string('margin_method')->nullable()->after('margin_value')->comment('Pack Quote Margin Method');
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
                'quote_type',
                'margin_value',
                'margin_method'
            ]);
        });
    }
}
