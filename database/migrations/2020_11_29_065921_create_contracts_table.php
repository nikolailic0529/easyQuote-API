<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->comment('Foreign key on users table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('quote_id')->nullable()->comment('Foreign key on quotes table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('customer_id')->nullable()->comment('Foreign key on customers table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->foreignUuid('distributor_file_id')->nullable()->comment('Foreign key on quotes table')->constrained('quote_files')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('schedule_file_id')->nullable()->comment('Foreign key on quotes table')->constrained('quote_files')->nullOnDelete()->cascadeOnUpdate();

            $table->foreignUuid('company_id')->nullable()->comment('Foreign key on companies table')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('vendor_id')->nullable()->comment('Foreign key on vendors table')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('country_id')->nullable()->comment('Foreign key on countries table')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('contract_template_id')->nullable()->comment('Foreign key on contract_templates table')->constrained()->nullOnDelete()->cascadeOnUpdate();

            $table->string('contract_number')->comment('Contract number');
            $table->string('customer_name')->nullable()->comment('Customer name');
            $table->unsignedTinyInteger('completeness')->default(1)->comment('Contract completeness value');

            // TODO: create rows_groups table instead of the json column
            $table->json('group_description')->nullable()->comment('Contract group description');
            $table->json('sort_group_description')->nullable()->comment('Groups sorting columns');
            $table->boolean('use_groups')->default(false)->comment('Whether shall use or not grouped rows');

            $table->text('pricing_document')->nullable()->comment('Pricing document number');
            $table->text('service_agreement_id')->nullable()->comment('Service agreement ID');
            $table->text('system_handle')->nullable()->comment('System handle number');

            $table->text('additional_notes')->nullable()->comment('Contract additional notes');
            $table->text('additional_details')->nullable()->comment('Contract additional details');
            $table->date('contract_date')->nullable()->comment('Contract date');

            $table->json('previous_state')->nullable()->comment('Previous attributes state');

            $table->timestamps();
            $table->softDeletes()->index();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamp('activated_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contracts');
    }
}
