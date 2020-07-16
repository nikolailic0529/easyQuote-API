<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHpeContractDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hpe_contract_data', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('hpe_contract_file_id')->constrained('hpe_contract_files')->onDelete('cascade')->onUpdate('cascade');

            $table->string('amp_id')->nullable()->comment('AMP ID');

            $table->string('support_account_reference')->nullable()->comment('Support Account Reference'); // 1007703223_00002
            $table->string('contract_number')->nullable()->comment('Contract Number');
            $table->string('order_authorization')->nullable()->comment('Purchase Authorization Number');
            $table->date('contract_start_date')->nullable()->comment('Contract Start Date');
            $table->date('contract_end_date')->nullable()->comment('Contract Start Date');

            $table->float('price')->default(0.0)->comment('Price');

            $table->string('product_number')->nullable()->comment('Product Number');
            $table->string('serial_number')->nullable()->comment('Serial Number');
            $table->string('product_description')->nullable()->comment('Product Description');
            $table->unsignedMediumInteger('product_quantity')->default(0)->comment('Product Quantity');

            $table->string('asset_type')->nullable()->comment('Asset Type');
            $table->string('service_type')->nullable()->comment('Service Type');

            $table->string('service_code')->nullable()->comment('Service Code');
            $table->string('service_description')->nullable()->comment('Service Description');

            $table->string('service_code_2')->nullable()->comment('Service Code 2');
            $table->string('service_description_2')->nullable()->comment('Service Description 2');

            $table->text('service_levels')->nullable()->comment('Semicolon separated Service Levels');

            $table->string('hw_delivery_contact_name')->nullable()->comment('HW Delivery Contact Name');
            $table->string('hw_delivery_contact_phone')->nullable()->comment('HW Delivery Contact Phone');
            $table->string('sw_delivery_contact_name')->nullable()->comment('SW Delivery Contact Name');
            $table->string('sw_delivery_contact_phone')->nullable()->comment('SW Delivery Contact Phone');
            $table->string('pr_support_contact_name')->nullable()->comment('Primary Support Recipient Name');
            $table->string('pr_support_contact_phone')->nullable()->comment('Primary Support Recipient Phone');

            $table->string('customer_name')->nullable()->comment('Customer Name');

            $table->string('customer_address')->nullable()->comment('Customer Address');
            $table->string('customer_city')->nullable()->comment('Customer City');
            $table->string('customer_post_code')->nullable()->comment('Customer Post Code');
            $table->string('customer_country_code')->nullable()->comment('Customer Country Code');

            $table->date('support_start_date')->nullable()->comment('Coverage Start Date');
            $table->date('support_end_date')->nullable()->comment('Coverage End Date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('hpe_contract_data');

        Schema::enableForeignKeyConstraints();
    }
}
