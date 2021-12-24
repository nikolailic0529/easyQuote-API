<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorldwideQuoteAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('worldwide_quote_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('worldwide_quote_id')->nullable()->comment('Foreign key on worldwide_quotes table')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignUuid('vendor_id')->nullable()->comment('Foreign key on vendors table')->constrained()->cascadeOnUpdate()->nullOnDelete();

            $table->foreignUuid('machine_address_id')->nullable()->comment('Foreign key on addresses table')->constrained('addresses')->cascadeOnUpdate()->nullOnDelete();

            $table->string('serial_no')->nullable()->comment('Asset Serial Number');
            $table->string('sku')->nullable()->comment('Asset SKU');
            $table->string('product_no')->nullable()->comment('Asset Product Number');
            $table->date('expiry_date')->nullable()->comment('Asset Expiry Date');
            $table->string('service_level')->nullable()->comment('Asset Service Level');
            $table->float('price')->nullable()->comment('Asset Price');
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

        Schema::dropIfExists('worldwide_quote_assets');

        Schema::enableForeignKeyConstraints();
    }
}
