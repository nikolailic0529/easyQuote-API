<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBaseOpportunityAmountBaseBaseListPriceBasePurchasePriceOpportunitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->unsignedFloat('base_opportunity_amount')->nullable()->after('opportunity_amount')->comment('Opportunity amount in GBP currency');
            $table->unsignedFloat('base_list_price')->nullable()->after('list_price')->comment('Opportunity list price in GBP currency');
            $table->unsignedFloat('base_purchase_price')->nullable()->after('purchase_price')->comment('Opportunity purchase price in GBP currency');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn([
                'base_opportunity_amount',
                'base_list_price',
                'base_purchase_price',
            ]);
        });
    }
}
