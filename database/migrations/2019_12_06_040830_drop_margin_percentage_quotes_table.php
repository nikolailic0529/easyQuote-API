<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropMarginPercentageQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('margin_percentage');
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
            $table->float('margin_percentage')->default(0.0);
        });
    }
}
