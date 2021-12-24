<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('worldwide_quote_id')->comment('Foreign key on worldwide_quotes table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('contract_template_id')->comment('Foreign key on contract_templates table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->date('order_date')->nullable()->comment('Date the Order was sent');

            $table->string('vat_number')->comment('VAT Number');
            $table->string('customer_po')->comment('Purchase Order Number');

            $table->timestamps();
            $table->softDeletes()->index();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamp('activated_at')->nullable()->useCurrent();

            $table->unique(['worldwide_quote_id', 'deleted_at']);
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->boolean('is_active')->after('activated_at')->virtualAs(DB::raw('activated_at IS NOT NULL'));
            $table->index('is_active');
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

        Schema::dropIfExists('sales_orders');

        Schema::enableForeignKeyConstraints();
    }
}
