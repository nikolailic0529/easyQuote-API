<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCountrySalesOrderTemplateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('country_sales_order_template', function (Blueprint $table) {

            $table->foreignUuid('country_id')->comment('Foreign key on countries table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('sales_order_template_id')->comment('Foreign key on sales_order_templates table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->primary(['country_id', 'sales_order_template_id'], 'country_sales_order_template_primary');

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

        Schema::dropIfExists('country_sales_order_template');

        Schema::enableForeignKeyConstraints();
    }
}
