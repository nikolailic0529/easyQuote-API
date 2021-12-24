<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesOrderTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_order_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->nullable()->comment('Foreign key on users table, Owner of the record')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('business_division_id')->comment('Foreign key on business_divisions table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('contract_type_id')->comment('Foreign key on contract_types table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->foreignUuid('company_id')->comment('Foreign key on companies table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('vendor_id')->comment('Foreign key on vendors table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('currency_id')->nullable()->comment('Foreign key on currencies table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->foreignUuid('template_schema_id')->comment('Foreign key on template_schemas table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->string('name')->comment('Template Name');

            $table->boolean('is_system')->default(0)->comment('Whether the record is system-wide');

            $table->timestamps();
            $table->softDeletes()->index();
            $table->timestamp('activated_at')->nullable()->useCurrent();
            $table->boolean('is_active')->virtualAs(\Illuminate\Support\Facades\DB::raw('activated_at IS NOT NULL'));
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

        Schema::dropIfExists('sales_order_templates');

        Schema::enableForeignKeyConstraints();
    }
}
