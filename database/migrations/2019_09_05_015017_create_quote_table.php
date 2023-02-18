<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuoteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->uuid('quote_template_id')->nullable();
            $table->foreign('quote_template_id')->references('id')->on('quote_templates')->onDelete('set null');

            $table->uuid('contract_template_id')->nullable();
            $table->foreign('contract_template_id')->references('id')->on('quote_templates')->onDelete('set null');

            $table->uuid('company_id')->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');

            $table->uuid('vendor_id')->nullable();
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');

            $table->uuid('country_id')->nullable();
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');

            $table->uuid('source_currency_id')->nullable();
            $table->foreign('source_currency_id')->references('id')->on('currencies')->onDelete('set null');

            $table->uuid('target_currency_id')->nullable();
            $table->foreign('target_currency_id')->references('id')->on('currencies')->onDelete('set null');

            $table->string('document_type')->index();

            $table->set('type', ['New', 'Renewal'])->nullable();
            $table->tinyInteger('completeness')->default(1);

            $table->json('margin_data')->nullable();
            $table->json('checkbox_status')->nullable();

            $table->json('group_description')->nullable();
            $table->json('sort_group_description')->nullable();

            $table->json('cached_relations')->nullable();
            $table->json('previous_state')->nullable();

            $table->decimal('custom_discount')->default(0.0);
            $table->decimal('buy_price', 15, 2)->nullable();
            $table->decimal('exchange_rate_margin')->nullable();

            $table->boolean('calculate_list_price')->default(false);
            $table->boolean('use_groups')->default(false);

            $table->boolean('is_version')->default(false)->index()->comment('Determine whether quote is version');
            $table->integer('version_number')->nullable();

            $table->text('pricing_document')->nullable();
            $table->text('service_agreement_id')->nullable();
            $table->text('system_handle')->nullable();

            $table->text('additional_details')->nullable();
            $table->text('additional_notes')->nullable();

            $table->date('closing_date')->nullable();

            $table->timestamps();
            $table->timestamp('submitted_at')->index()->nullable();
            $table->timestamp('activated_at')->index()->nullable();
            $table->timestamp('drafted_at')->nullable()->default(null);
            $table->softDeletes()->index();
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->uuid('quote_id')->nullable()->after('id');
            $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quotes');
    }
}
