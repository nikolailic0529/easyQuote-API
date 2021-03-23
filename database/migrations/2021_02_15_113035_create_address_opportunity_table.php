<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddressOpportunityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('address_opportunity', function (Blueprint $table) {
            $table->foreignUuid('address_id')->comment('Foreign key on addresses table')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignUuid('opportunity_id')->comment('Foreign key on opportunities table')->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            $table->primary(['opportunity_id', 'address_id']);
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

        Schema::dropIfExists('address_opportunity');

        Schema::enableForeignKeyConstraints();
    }
}
