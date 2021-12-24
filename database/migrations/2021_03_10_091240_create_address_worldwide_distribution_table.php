<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddressWorldwideDistributionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('address_worldwide_distribution', function (Blueprint $table) {
            $table->foreignUuid('address_id')->comment('Foreign key on addresses table')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignUuid('worldwide_distribution_id')->comment('Foreign key on worldwide_distributions table')->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            $table->boolean('is_default')->default(false)->comment('Whether the address is default for the entity');

            $table->primary(['worldwide_distribution_id', 'address_id'], 'address_worldwide_distribution_primary');
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

        Schema::dropIfExists('address_worldwide_distribution');

        Schema::enableForeignKeyConstraints();
    }
}
