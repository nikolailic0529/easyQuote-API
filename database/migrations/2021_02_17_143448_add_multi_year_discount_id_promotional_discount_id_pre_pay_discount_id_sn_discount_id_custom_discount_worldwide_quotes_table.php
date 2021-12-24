<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMultiYearDiscountIdPromotionalDiscountIdPrePayDiscountIdSnDiscountIdCustomDiscountWorldwideQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quotes', function (Blueprint $table) {

            $table->foreignUuid('multi_year_discount_id')->nullable()->after('output_currency_id')->comment('Foreign key on multi_year_discounts table')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('pre_pay_discount_id')->nullable()->after('multi_year_discount_id')->comment('Foreign key on pre_pay_discounts table')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('promotional_discount_id')->nullable()->after('pre_pay_discount_id')->comment('Foreign key on promotional_discounts table')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('sn_discount_id')->nullable()->after('promotional_discount_id')->comment('Foreign key on sn_discounts table')->constrained()->nullOnDelete()->cascadeOnUpdate();

            $table->decimal('custom_discount')->nullable()->after('quote_expiry_date')->comment('Custom discount value');

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
            $table->dropForeign(['multi_year_discount_id']);
            $table->dropForeign(['pre_pay_discount_id']);
            $table->dropForeign(['promotional_discount_id']);
            $table->dropForeign(['sn_discount_id']);

            $table->dropColumn([
                'multi_year_discount_id',
                'pre_pay_discount_id',
                'promotional_discount_id',
                'sn_discount_id',

                'custom_discount'
            ]);
        });
    }
}
