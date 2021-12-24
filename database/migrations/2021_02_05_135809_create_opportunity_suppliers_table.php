<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOpportunitySuppliersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('opportunity_suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('opportunity_id')->comment('Foreign key on opportunities table')->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            $table->string('supplier_name')->nullable()->comment('Supplier Name');
            $table->string('country_name')->nullable()->comment('Supplier Country Name');
            $table->string('contact_name')->nullable()->comment('Supplier Contact Name');
            $table->string('contact_email')->nullable()->comment('Supplier Contact Email Address');

            $table->timestamps();
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

        Schema::dropIfExists('opportunity_suppliers');

        Schema::enableForeignKeyConstraints();
    }
}
