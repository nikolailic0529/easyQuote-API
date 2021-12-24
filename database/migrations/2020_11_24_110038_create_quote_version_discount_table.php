<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuoteVersionDiscountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quote_version_discount', function (Blueprint $table) {
            $table->foreignUuid('quote_version_id')->comment('Foreign key on quote_versions table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('discount_id')->comment('Foreign key on discounts table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->unsignedTinyInteger('duration')->nullable()->comment('Discount duration');
            $table->decimal('margin_percentage')->nullable()->comment('Calculated margin percentage');

            $table->primary(['quote_version_id', 'discount_id'], 'quote_version_discount_primary');
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

        Schema::dropIfExists('quote_version_discount');

        Schema::enableForeignKeyConstraints();
    }
}
