<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorldwideQuoteVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('worldwide_quote_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('worldwide_quote_id')->comment('Foreign key on worldwide_quotes table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('user_id')->comment('Foreign key on users table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('company_id')->nullable()->comment('Foreign key on companies table')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('quote_template_id')->nullable()->comment('Foreign key on quote_templates table')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('quote_currency_id')->nullable()->comment('Foreign key on currencies table')->constrained('currencies')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('output_currency_id')->nullable()->comment('Foreign key on currencies table')->constrained('currencies')->nullOnDelete()->cascadeOnUpdate();

            $table->foreignUuid('multi_year_discount_id')->nullable()->comment('Foreign key on multi_year_discounts table')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('pre_pay_discount_id')->nullable()->comment('Foreign key on pre_pay_discounts table')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('promotional_discount_id')->nullable()->comment('Foreign key on promotional_discounts table')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('sn_discount_id')->nullable()->comment('Foreign key on sn_discounts table')->constrained()->nullOnDelete()->cascadeOnUpdate();

            $table->decimal('custom_discount')->nullable()->comment('Custom discount value');

            $table->unsignedInteger('sequence_number')->comment('Sequence number of the Quote Version');

            $table->string('quote_type')->nullable()->comment('Pack Quote Type (New, Renewal)');
            $table->float('margin_value')->nullable()->comment('Pack Quote Margin Value');
            $table->string('margin_method')->nullable()->comment('Pack Quote Margin Method');
            $table->float('tax_value')->nullable()->comment('Pack Quote Tax Value');

            $table->decimal('buy_price', 15)->nullable()->comment('Quote Buy Price');

            $table->enum('sort_rows_column', ['sku', 'serial_no', 'product_name', 'expiry_date', 'price', 'service_level_description', 'vendor_short_code', 'machine_address'])
                ->nullable()
                ->comment('Column name on the worldwide_quote_assets table for sorting');

            $table->enum('sort_rows_direction', ['asc', 'desc'])
                ->default('asc')
                ->comment('Sorting direction of the pack assets');

            $table->decimal('exchange_rate_margin')->nullable()->comment('Exchange Rate Margin');
            $table->date('quote_expiry_date')->nullable()->comment('Quote Expiration Date');

            $table->string('pricing_document', 1000)->nullable()->comment('Pack Quote Pricing Document Number');
            $table->string('service_agreement_id', 1000)->nullable()->comment('Pack Quote Service Agreement ID');
            $table->string('system_handle', 1000)->nullable()->comment('Pack Quote System Handle');
            $table->text('additional_details')->nullable()->comment('Pack Quote Additional Details');
            $table->text('additional_notes')->nullable()->comment('Quote Additional Notes');

            $table->unsignedTinyInteger('completeness')->default(1)->comment('Quote completeness value');

            $table->unique(['worldwide_quote_id', 'sequence_number', 'deleted_at'], 'worldwide_quote_versions_sequence_unique');

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

        Schema::dropIfExists('worldwide_quote_versions');

        Schema::enableForeignKeyConstraints();
    }
}
