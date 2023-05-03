<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorldwideQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('worldwide_quotes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->comment('Foreign key on users table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('worldwide_customer_id')->comment('Foreign key on worldwide_customers table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('company_id')->nullable()->comment('Foreign key on companies table')->constrained()->nullOnDelete()->cascadeOnUpdate();

            $table->foreignUuid('quote_currency_id')->nullable()->comment('Foreign key on currencies table')->constrained('currencies')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('output_currency_id')->nullable()->comment('Foreign key on currencies table')->constrained('currencies')->nullOnDelete()->cascadeOnUpdate();
            $table->decimal('exchange_rate_margin')->nullable()->comment('Exchange Rate margin per quote');

            $table->unsignedTinyInteger('completeness')->default(1)->comment('Quote completeness value');
            $table->date('closing_date')->nullable()->comment('Quote closing date');
            $table->json('checkbox_status')->nullable()->comment('Quote form checkboxes');

            $table->timestamps();
            $table->softDeletes()->index();

            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamp('activated_at')->nullable()->useCurrent()->index();
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

        Schema::dropIfExists('worldwide_quotes');

        Schema::enableForeignKeyConstraints();
    }
}
