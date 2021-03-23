<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCustomerTotalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_totals', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('address_id')->comment('Foreign key on addresses table');
            $table->foreign('address_id')->references('id')->on('addresses')->onUpdate('cascade')->onDelete('cascade');

            $table->uuid('location_id')->comment('Foreign key on locations table');
            $table->foreign('location_id')->references('id')->on('locations')->onUpdate('cascade')->onDelete('cascade');
            
            $table->string('customer_name')->index()->comment('Unique customer name');
            
            $table->point('location_coordinates')->spatialIndex()->comment('Customer location coordinates');

            $table->double('lat')->storedAs(DB::raw('Y(`location_coordinates`)'));
            $table->double('lng')->storedAs(DB::raw('X(`location_coordinates`)'));

            $table->string('location_address')->comment('Location formatted address');
            
            $table->decimal('total_value', 15, 2)->default(0)->comment('Customer total value');
            $table->unsignedBigInteger('total_count')->default(0)->comment('Customer total count');

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
        Schema::dropIfExists('customer_totals');
    }
}
