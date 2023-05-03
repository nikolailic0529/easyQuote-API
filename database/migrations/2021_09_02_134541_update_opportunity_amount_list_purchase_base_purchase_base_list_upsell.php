<?php

use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\Type;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateOpportunityAmountListPurchaseBasePurchaseBaseListUpsell extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Type::hasType('double')) {
            Type::addType('double', FloatType::class);
        }
        Schema::table('opportunities', function (Blueprint $table) {
            DB::statement('ALTER TABLE `opportunities`  
                    CHANGE  `opportunity_amount` `opportunity_amount` DOUBLE(20,2) NULL DEFAULT NULL,
                    CHANGE  `purchase_price` `purchase_price` DOUBLE(20,2) NULL DEFAULT NULL,
                    CHANGE  `list_price` `list_price` DOUBLE(20,2) NULL DEFAULT NULL,
                    CHANGE  `estimated_upsell_amount` `estimated_upsell_amount` DOUBLE(20,2) NULL DEFAULT NULL,
                    CHANGE  `base_opportunity_amount` `base_opportunity_amount` DOUBLE(20,2) NULL DEFAULT NULL,
                    CHANGE  `base_list_price` `base_list_price` DOUBLE(20,2) NULL DEFAULT NULL,
                    CHANGE  `base_purchase_price` `base_purchase_price` DOUBLE(20,2) NULL DEFAULT NULL');
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
            $table->float('opportunity_amount')->nullable()->change();
            $table->float('purchase_price')->nullable()->change();
            $table->float('list_price')->nullable()->change();
            $table->float('estimated_upsell_amount')->nullable()->change();
            $table->unsignedFloat('base_opportunity_amount')->nullable()->change();
            $table->unsignedFloat('base_list_price')->nullable()->change();
            $table->unsignedFloat('base_purchase_price')->nullable()->change();
        });
    }
}
