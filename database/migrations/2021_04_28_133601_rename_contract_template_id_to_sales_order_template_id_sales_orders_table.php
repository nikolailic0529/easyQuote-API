<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameContractTemplateIdToSalesOrderTemplateIdSalesOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::table('sales_orders')->exists()) {
            throw new RuntimeException('Please delete all records from sales_orders table to proceed.');
        }

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contract_template_id');

            $table->foreignUuid('sales_order_template_id')->after('worldwide_quote_id')->comment('Foreign key on sales_order_templates table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (DB::table('sales_orders')->exists()) {
            throw new RuntimeException('Please delete all records from sales_orders table to proceed.');
        }

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_order_template_id');

            $table->foreignUuid('contract_template_id')->after('worldwide_quote_id')->comment('Foreign key on contract_templates table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
}
