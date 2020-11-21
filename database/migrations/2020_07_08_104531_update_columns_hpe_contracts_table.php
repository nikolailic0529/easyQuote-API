<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateColumnsHpeContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hpe_contracts', function (Blueprint $table) {
            $table->string('amp_id')->nullable()->after('hpe_contract_file_id')->comment('Contract AMP ID');
            $table->string('support_account_reference')->nullable()->after('amp_id')->comment('Support Account Reference');
            $table->json('orders_authorization')->nullable()->after('amp_id')->comment('Orders Authorization Numbers');
            $table->json('contract_numbers')->nullable()->after('orders_authorization')->comment('Contract Numbers');

            $table->string('customer_name', 250)->nullable()->after('contract_numbers')->comment('Customer Name');
            $table->string('customer_address', 250)->nullable()->after('customer_name')->comment('Customer Address');
            $table->string('customer_city')->nullable()->after('customer_address')->comment('Customer City');
            $table->string('customer_post_code')->nullable()->after('customer_city')->comment('Customer Post Code');
            $table->string('customer_country_code')->nullable()->after('customer_post_code')->comment('Customer Country Code');

            $table->string('hw_delivery_contact_name', 250)->nullable()->after('customer_country_code')->comment('Customer HW Delivery Contact Name');
            $table->string('hw_delivery_contact_phone')->nullable()->after('hw_delivery_contact_name')->comment('Customer HW Delivery Contact Phone');
            $table->string('sw_delivery_contact_name', 250)->nullable()->after('hw_delivery_contact_phone')->comment('Customer SW Delivery Contact Name');
            $table->string('sw_delivery_contact_phone')->nullable()->after('sw_delivery_contact_name')->comment('Customer SW Delivery Contact Phone');
            $table->string('pr_support_contact_name', 250)->nullable()->after('sw_delivery_contact_phone')->comment('Customer Primary Support Contact Name');
            $table->string('pr_support_contact_phone')->nullable()->after('pr_support_contact_name')->comment('Customer Primary Support Contact Phone');
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
                'amp_id',
                'orders_authorization',
                'contract_numbers',
                'customer_name',
                'customer_address',
                'customer_city',
                'customer_post_code',
                'customer_country_code',

                'hw_delivery_contact_name',
                'hw_delivery_contact_phone',
                'sw_delivery_contact_name',
                'sw_delivery_contact_phone',
                'pr_support_contact_name',
                'pr_support_contact_phone',
            ]);
        });
    }
}
