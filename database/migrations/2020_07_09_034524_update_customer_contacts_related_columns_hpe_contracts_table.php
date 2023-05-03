<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCustomerContactsRelatedColumnsHpeContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hpe_contracts', function (Blueprint $table) {
            $table->dropColumn([
                'hw_delivery_contact_name',
                'hw_delivery_contact_phone',
                'sw_delivery_contact_name',
                'sw_delivery_contact_phone',
                'pr_support_contact_name',
                'pr_support_contact_phone',
            ]);

            $table->json('sold_contact')->nullable()->comment('Customer Sold Contact');
            $table->json('bill_contact')->nullable()->comment('Customer Bill Contact');
            $table->json('hw_delivery_contact')->nullable()->comment('Customer HW Delivery Contact');
            $table->json('sw_delivery_contact')->nullable()->comment('Customer SW Delivery Contact');
            $table->json('pr_support_contact')->nullable()->comment('Customer Primary Support Contact');
            $table->json('entitled_party_contact')->nullable()->comment('Customer Entitled Party Contact');
            $table->json('end_customer_contact')->nullable()->comment('End Customer Contact');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hpe_contracts', function (Blueprint $table) {
            $table->dropColumn([
                'hw_delivery_contact',
                'sw_delivery_contact',
                'pr_support_contact',
                'entitled_party_contact',
                'end_customer_contact',
                'sold_contact',
                'bill_contact',
            ]);

            $table->string('hw_delivery_contact_name', 250)->nullable()->after('customer_country_code')->comment('Customer HW Delivery Contact Name');
            $table->string('hw_delivery_contact_phone')->nullable()->after('hw_delivery_contact_name')->comment('Customer HW Delivery Contact Phone');
            $table->string('sw_delivery_contact_name', 250)->nullable()->after('hw_delivery_contact_phone')->comment('Customer SW Delivery Contact Name');
            $table->string('sw_delivery_contact_phone')->nullable()->after('sw_delivery_contact_name')->comment('Customer SW Delivery Contact Phone');
            $table->string('pr_support_contact_name', 250)->nullable()->after('sw_delivery_contact_phone')->comment('Customer Primary Support Contact Name');
            $table->string('pr_support_contact_phone')->nullable()->after('pr_support_contact_name')->comment('Customer Primary Support Contact Phone');
        });
    }
}
