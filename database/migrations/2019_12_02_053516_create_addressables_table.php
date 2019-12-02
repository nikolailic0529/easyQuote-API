<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddressablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('company_address');
        Schema::dropIfExists('customer_address');

        Schema::create('addressables', function (Blueprint $table) {
            $table->uuid('address_id');
            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('cascade');
            $table->uuid('addressable_id');
            $table->string('addressable_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('addressables');

        Schema::create('company_address', function (Blueprint $table) {
            $table->uuid('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->uuid('address_id');
            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('cascade');
        });

        Schema::create('customer_address', function (Blueprint $table) {
            $table->uuid('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->uuid('address_id');
            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('cascade');
        });
    }
}
