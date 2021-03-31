<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCountryOpportunityTotalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('country_opportunity_total', function (Blueprint $table) {
            $table->foreignUuid('opportunity_total_id')->comment('Foreign key on opportunity_totals table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('country_id')->comment('Foreign key on countries table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->primary(['opportunity_total_id', 'country_id'], 'opportunity_total_country_primary');
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

        Schema::dropIfExists('country_opportunity_total');

        Schema::enableForeignKeyConstraints();
    }
}
