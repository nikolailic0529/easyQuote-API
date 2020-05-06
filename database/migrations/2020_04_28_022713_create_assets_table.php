<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('set null');

            $table->uuid('address_id')->nullable();
            $table->foreign('address_id')->references('id')->on('addresses')->onUpdate('cascade')->onDelete('set null');

            $table->uuid('vendor_id');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onUpdate('cascade')->onDelete('cascade');

            $table->char('vendor_short_code', 3)->index();

            $table->decimal('unit_price', 15)->default(0);

            $table->timestamp('base_warranty_start_date')->nullable();
            $table->timestamp('base_warranty_end_date')->nullable();

            $table->timestamp('active_warranty_start_date')->nullable();
            $table->timestamp('active_warranty_end_date')->nullable();

            $table->string('product_number')->nullable()->comment('Asset SKU');
            $table->string('serial_number')->nullable()->comment('Asset serial number / type');            
            $table->string('item_number')->nullable()->comment('Asset item number');

            $table->string('product_description')->nullable()->comment('Asset friendly name');
            $table->string('service_description')->nullable()->comment('Asset service description');

            $table->index(['product_number', 'serial_number']);
            $table->index(['base_warranty_start_date', 'base_warranty_end_date']);
            $table->index(['active_warranty_start_date', 'active_warranty_end_date']);

            $table->boolean('is_migrated')->default(false)->comment('Whether asset migrated');

            $table->timestamps();
            $table->softDeletes()->index();
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

        Schema::dropIfExists('assets');

        Schema::enableForeignKeyConstraints();
    }
}
