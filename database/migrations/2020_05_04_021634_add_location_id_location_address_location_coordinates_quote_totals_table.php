<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLocationIdLocationAddressLocationCoordinatesQuoteTotalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            DB::table('quote_totals')->truncate();


            Schema::table('quote_totals', function (Blueprint $table) {
                $table->uuid('location_id')->after('customer_id');
                $table->foreign('location_id')->references('id')->on('locations')->onUpdate('cascade')->onDelete('cascade');

                $table->string('location_address')->after('rfq_number');
                $table->point('location_coordinates')->spatialIndex()->after('location_address');
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quote_totals', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropSpatialIndex(['location_coordinates']);

            $table->dropColumn(['location_id', 'location_address', 'location_coordinates']);
        });
    }
}
