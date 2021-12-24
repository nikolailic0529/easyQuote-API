<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMarginValueWorldwideDistributionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_distributions', function (Blueprint $table) {
            $table->string('quote_type')->nullable()->after('distribution_currency_id')->comment('Distribution quote type (New, Renewal)');
            $table->float('margin_value')->nullable()->after('custom_discount')->comment('Distribution margin value');
            $table->string('margin_method')->nullable()->after('margin_value')->comment('Distribution margin method');
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
            $table->dropColumn([
                'quote_type',
                'margin_value',
                'margin_method',
            ]);
        });
    }
}
