<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVendorWorldwideDistributionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vendor_worldwide_distribution', function (Blueprint $table) {
            $table->foreignUuid('worldwide_distribution_id')->comment('Foreign key on worldwide_distributions table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('vendor_id')->comment('Foreign key on vendors table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->primary(['worldwide_distribution_id', 'vendor_id'], 'worldwide_distribution_vendor_primary');
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

        Schema::dropIfExists('vendor_worldwide_distribution');

        Schema::enableForeignKeyConstraints();
    }
}
