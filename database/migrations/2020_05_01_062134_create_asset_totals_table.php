<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetTotalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asset_totals', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('location_id');
            $table->foreign('location_id')->references('id')->on('locations')->onUpdate('cascade')->onDelete('cascade');

            $table->point('location_coordinates')->spatialIndex();
            $table->string('location_address');

            $table->double('lat')->storedAs(DB::raw('Y(`location_coordinates`)'));
            $table->double('lng')->storedAs(DB::raw('X(`location_coordinates`)'));

            $table->decimal('total_value', 15)->default(0);
            $table->unsignedBigInteger('total_count')->default(0);
            
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
        Schema::dropIfExists('asset_totals');
    }
}
