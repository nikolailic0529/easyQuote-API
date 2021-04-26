<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddressWorldwideQuoteVersionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('address_worldwide_quote_version', function (Blueprint $table) {
            $table->foreignUuid('address_id')->comment('Foreign key on addresses table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->uuid('worldwide_quote_version_id')->comment('Foreign key on worldwide_quote_versions table');
            $table->foreign('worldwide_quote_version_id', 'a_wqv_worldwide_quote_version_id_foreign')->references('id')->on('worldwide_quote_versions')->cascadeOnDelete()->cascadeOnUpdate();

            $table->primary(['address_id', 'worldwide_quote_version_id'], 'address_worldwide_quote_version_primary');
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

        Schema::dropIfExists('address_worldwide_quote_version');

        Schema::enableForeignKeyConstraints();
    }
}
