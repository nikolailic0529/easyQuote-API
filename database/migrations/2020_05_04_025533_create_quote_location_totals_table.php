<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuoteLocationTotalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quote_location_totals', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('location_id');
            $table->foreign('location_id')->references('id')->on('locations')->onUpdate('cascade')->onDelete('cascade');

            $table->string('location_address');
            $table->point('location_coordinates')->spatialIndex();

            $table->double('lat')->storedAs(DB::raw('ST_Y(`location_coordinates`)'));
            $table->double('lng')->storedAs(DB::raw('ST_X(`location_coordinates`)'));

            $table->unsignedBigInteger('total_drafted_count')->default(0);
            $table->unsignedBigInteger('total_submitted_count')->default(0);

            $table->decimal('total_drafted_value', 15)->default(0);
            $table->decimal('total_submitted_value', 15)->default(0);

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
        Schema::dropIfExists('quote_location_totals');
    }
}
