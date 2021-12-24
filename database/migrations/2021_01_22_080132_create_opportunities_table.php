<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOpportunitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->comment('Foreign key on users table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->foreignUuid('primary_account_id')->nullable()->comment('Foreign key on companies table')->constrained('companies')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('primary_account_contact_id')->nullable()->comment('Foreign key on contacts table')->constrained('contacts')->nullOnDelete()->cascadeOnUpdate();

            $table->foreignUuid('account_manager_id')->nullable()->comment('Foreign key on users table')->constrained('users')->nullOnDelete()->cascadeOnUpdate();

            $table->string('project_name')->nullable()->comment('Project name');

            $table->string('nature_of_service')->nullable()->comment('Nature of service');
            $table->string('sale_action_name')->nullable()->comment('Sale Action Name'); // {"Preparation", "Special Bid Required", "Quote Ready", "Customer Contact", "Customer Order OK", "Purchase Order Placed", "Processed on BC", "Closed"}

            $table->string('customer_status')->nullable()->comment('Customer status');
            $table->string('end_user_name')->nullable()->comment('End user name');
            $table->string('hardware_status')->nullable()->comment('Hardware status');
            $table->string('region_name')->nullable()->comment('Region name');
            $table->string('account_manager_name')->nullable()->comment('Account manager name');
            $table->string('service_level_agreement_id')->nullable()->comment('Service level agreement ID');
            $table->string('sale_unit_name')->nullable()->comment('Sale Unit Name');
            $table->string('competition_name')->nullable()->comment('Competition name');
            $table->string('drop_in')->nullable()->comment('Drop In'); // ?
            $table->string('lead_source_name')->nullable()->comment('Lead Source name');

            $table->float('opportunity_amount')->nullable()->comment('Opportunity amount');
            $table->char('opportunity_amount_currency_code', 3)->nullable()->comment('Opportunity amount currency code');
            $table->float('purchase_price')->nullable()->comment('Purchase price');
            $table->char('purchase_price_currency_code', 3)->nullable()->comment('Purchase price currency code');
            $table->float('list_price')->nullable()->comment('List price');
            $table->char('list_price_currency_code', 3)->nullable()->comment('List price currency code');
            $table->float('estimated_upsell_amount')->nullable()->comment('Estimated Upsell amount');
            $table->char('estimated_upsell_amount_currency_code', 3)->nullable()->comment('Estimated Upsell amount currency code');
            $table->float('margin_value')->nullable()->comment('Margin value');

            $table->unsignedSmallInteger('renewal_month')->nullable()->comment('Renewal Month');
            $table->unsignedSmallInteger('renewal_year')->nullable()->comment('Renewal Year');

            $table->date('opportunity_start_date')->nullable()->comment('Opportunity Start Date');
            $table->date('opportunity_end_date')->nullable()->comment('Opportunity End Date');
            $table->date('opportunity_closing_date')->nullable()->comment('Opportunity closing date');
            $table->date('expected_order_date')->nullable()->comment('Expected Order Date');
            $table->date('customer_order_date')->nullable()->comment('Customer Order Date');
            $table->date('purchase_order_date')->nullable()->comment('Purchase Order Date');
            $table->date('supplier_order_date')->nullable()->comment('Supplier Order Date');
            $table->date('supplier_order_transaction_date')->nullable()->comment('Supplier Order Transaction Date');
            $table->date('supplier_order_confirmation_date')->nullable()->comment('Supplier Order Confirmation Date');

            $table->boolean('has_higher_sla')->default(false)->comment('Whether has Higher SLA');
            $table->boolean('is_multi_year')->default(false)->comment('Whether is multi year');
            $table->boolean('has_additional_hardware')->default(false)->comment('Whether has an additional hardware');
            $table->boolean('has_service_credits')->default(false)->comment('Whether has service credits');

            $table->unsignedSmallInteger('personal_rating')->nullable()->comment('Personal Rating');

            $table->text('remarks')->nullable()->comment('Opportunity remarks');

            $table->timestamps();
            $table->softDeletes()->index();
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

        Schema::dropIfExists('opportunities');

        Schema::enableForeignKeyConstraints();
    }
}
