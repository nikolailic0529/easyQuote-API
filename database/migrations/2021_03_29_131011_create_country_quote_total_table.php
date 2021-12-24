<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCountryQuoteTotalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('country_quote_total', function (Blueprint $table) {
            $table->foreignUuid('quote_total_id')->comment('Foreign key on quote_totals table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('country_id')->comment('Foreign key on countries table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->primary(['quote_total_id', 'country_id']);
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

        Schema::dropIfExists('country_quote_total');

        Schema::enableForeignKeyConstraints();
    }
}
