<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuoteTemplateVendorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('quote_template_vendor');

        Schema::create('quote_template_vendor', function (Blueprint $table) {
            $table->foreignUuid('quote_template_id')->comment('Foreign key on quote_templates table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('vendor_id')->comment('Foreign key on vendors table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->primary(['quote_template_id', 'vendor_id']);
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

        Schema::dropIfExists('quote_template_vendor');

        Schema::enableForeignKeyConstraints();
    }
}
