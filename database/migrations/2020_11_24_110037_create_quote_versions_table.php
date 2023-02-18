<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuoteVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quote_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->comment('Foreign key on users table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('quote_id')->comment('Foreign key on quotes table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('customer_id')->comment('Foreign key on customers table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->foreignUuid('distributor_file_id')->nullable()->comment('Foreign key on quotes table')->constrained('quote_files')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('schedule_file_id')->nullable()->comment('Foreign key on quotes table')->constrained('quote_files')->nullOnDelete()->cascadeOnUpdate();

            $table->foreignUuid('company_id')->nullable()->comment('Foreign key on companies table')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('vendor_id')->nullable()->comment('Foreign key on vendors table')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('country_id')->nullable()->comment('Foreign key on countries table')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('quote_template_id')->nullable()->comment('Foreign key on quote_templates table')->constrained()->nullOnDelete()->cascadeOnUpdate();

            $table->foreignUuid('country_margin_id')->nullable()->comment('Foreign key on country_margins table')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('source_currency_id')->nullable()->comment('Foreign key on currencies table')->constrained('currencies')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('target_currency_id')->nullable()->comment('Foreign key on currencies table')->constrained('currencies')->nullOnDelete()->cascadeOnUpdate();

            $table->unsignedTinyInteger('completeness')->default(1)->comment('Quote completeness value');

            // TODO: create rows_groups table instead of the json column
            $table->json('group_description')->nullable()->comment('Quote group description');
            $table->json('sort_group_description')->nullable()->comment('Groups sorting columns');

            $table->decimal('custom_discount')->nullable()->comment('Custom discount value');

            $table->decimal('buy_price', 15)->nullable()->comment('Quote Buy price');
            $table->decimal('exchange_rate_margin')->nullable()->comment('Exchange Rate margin per quote');

            $table->boolean('calculate_list_price')->default(false)->comment('Whether calculate or not list price');
            $table->boolean('use_groups')->default(false)->comment('Whether use or not grouped rows');

            $table->unsignedInteger('version_number')->default(1)->comment('Quote Version number');

            $table->text('pricing_document')->nullable()->comment('Pricing document number');
            $table->text('service_agreement_id')->nullable()->comment('Service agreement ID');
            $table->text('system_handle')->nullable()->comment('System handle number');

            $table->text('additional_details')->nullable()->comment('Quote additional details');
            $table->text('additional_notes')->nullable()->comment('Quote additional notes');
            $table->date('closing_date')->nullable()->comment('Quote closing date');

            $table->json('previous_state')->nullable()->comment('Previous attributes state');
            $table->json('checkbox_status')->nullable()->comment('Quote form checkboxes');

            $table->timestamps();
            $table->softDeletes()->index();
            $table->timestamp('assets_migrated_at')->nullable();
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

        Schema::dropIfExists('quote_versions');

        Schema::enableForeignKeyConstraints();
    }
}
