<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('worldwide_customers');

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('worldwide_customers', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('country_id')->nullable()->comment('Foreign key on countries table')->constrained()->nullOnDelete()->cascadeOnUpdate();

            $table->string('customer_name')->comment('Customer name');
            $table->string('rfq_number')->comment('Request for quote number');
            $table->string('source')->comment('Customer Source');

            $table->date('valid_until_date')->comment('Quote valid until date');
            $table->date('support_start_date')->comment('Quote support start date');
            $table->date('support_end_date')->comment('Quote support end date');

            $table->string('invoicing_terms')->nullable()->comment('Customer Invoice terms');
            $table->json('service_levels')->nullable()->comment('Customer Service levels');
            $table->string('customer_vat')->nullable()->comment('Customer VAT');
            $table->string('customer_email')->nullable()->comment('Customer Email');
            $table->string('customer_phone')->nullable()->comment('Customer phone');

            $table->timestamps();
            $table->softDeletes()->index();

            $table->unique(['rfq_number', 'deleted_at']);
        });
    }
};
