<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropVersionableColumnsWorldwideQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quotes', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['quote_template_id']);
            $table->dropForeign(['quote_currency_id']);
            $table->dropForeign(['output_currency_id']);
            $table->dropForeign(['multi_year_discount_id']);
            $table->dropForeign(['pre_pay_discount_id']);
            $table->dropForeign(['promotional_discount_id']);
            $table->dropForeign(['sn_discount_id']);

            $table->dropColumn([
                'worldwide_customer_id',
                'company_id',
                'quote_template_id',
                'quote_currency_id',
                'output_currency_id',
                'multi_year_discount_id',
                'pre_pay_discount_id',
                'promotional_discount_id',
                'sn_discount_id',
                'quote_type',
                'margin_value',
                'tax_value',
                'margin_method',
                'sort_rows_column',
                'sort_rows_direction',
                'buy_price',
                'exchange_rate_margin',
                'completeness',
                'quote_expiry_date',
                'custom_discount',
                'payment_terms',
                'closing_date',
                'pricing_document',
                'service_agreement_id',
                'system_handle',
                'additional_details',
                'additional_notes',
                'checkbox_status',
                'assets_migrated_at'
            ]);
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
            $table->uuid('worldwide_customer_id')->nullable()->after('user_id')->comment('Foreign key on worldwide_customers table');
            $table->foreignUuid('company_id')->nullable()->after('worldwide_customer_id')->comment('Foreign key on companies table')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('quote_template_id')->nullable()->after('company_id')->comment('Foreign key on quote_templates table')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('quote_currency_id')->nullable()->after('quote_template_id')->comment('Foreign key on currencies table')->constrained('currencies')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('output_currency_id')->nullable()->after('quote_currency_id')->comment('Foreign key on currencies table')->constrained('currencies')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('multi_year_discount_id')->nullable()->after('output_currency_id')->comment('Foreign key on multi_year_discounts table')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('pre_pay_discount_id')->nullable()->after('multi_year_discount_id')->comment('Foreign key on pre_pay_discounts table')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('promotional_discount_id')->nullable()->after('pre_pay_discount_id')->comment('Foreign key on promotional_discounts table')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('sn_discount_id')->nullable()->after('promotional_discount_id')->comment('Foreign key on sn_discounts table')->constrained()->nullOnDelete()->cascadeOnUpdate();

            $table->string('quote_type')->nullable()->after('sequence_number')->comment('Pack Quote Type (New, Renewal)');
            $table->float('margin_value')->nullable()->after('quote_type')->comment('Pack Quote Margin Value');
            $table->float('tax_value')->nullable()->after('margin_value')->comment('Quote Tax value');
            $table->string('margin_method')->nullable()->after('margin_value')->comment('Pack Quote Margin Method');
            $table->enum('sort_rows_column', ['sku', 'service_sku', 'serial_no', 'product_name', 'expiry_date', 'price', 'service_level_description', 'vendor_short_code', 'machine_address'])->nullable()->after('margin_method')->comment('Column name on the worldwide_quote_assets table for sorting');
            $table->enum('sort_rows_direction', ['asc', 'desc'])->default('asc')->after('sort_rows_column')->comment('Sorting direction of the pack assets');
            $table->decimal('buy_price', 15)->nullable()->after('margin_method')->comment('Quote Buy Price');
            $table->decimal('exchange_rate_margin')->nullable()->after('buy_price')->comment('Exchange Rate margin per quote');
            $table->date('quote_expiry_date')->nullable()->after('exchange_rate_margin')->comment('Quote Expiration Date');
            $table->decimal('custom_discount')->nullable()->after('quote_expiry_date')->comment('Custom discount value');
            $table->string('payment_terms', 500)->nullable()->after('custom_discount')->comment('Quote Payment Terms');
            $table->date('closing_date')->nullable()->comment('Quote closing date');
            $table->string('pricing_document', 1000)->nullable()->after('closing_date')->comment('Pack Quote Pricing Document Number');
            $table->string('service_agreement_id', 1000)->nullable()->after('pricing_document')->comment('Pack Quote Service Agreement ID');
            $table->string('system_handle', 1000)->nullable()->after('service_agreement_id')->comment('Pack Quote System Handle');
            $table->text('additional_details')->nullable()->after('system_handle')->comment('Pack Quote Additional Details');
            $table->text('additional_notes')->nullable()->after('additional_details')->comment('Quote Additional Notes');
            $table->json('checkbox_status')->nullable()->after('additional_notes')->comment('Quote form checkboxes');

            $table->unsignedTinyInteger('completeness')->default(1)->after('checkbox_status')->comment('Quote completeness value');

            $table->timestamp('assets_migrated_at')->nullable();
        });
    }
}
