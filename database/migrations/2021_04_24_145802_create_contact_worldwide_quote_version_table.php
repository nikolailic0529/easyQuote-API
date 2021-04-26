<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactWorldwideQuoteVersionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_worldwide_quote_version', function (Blueprint $table) {
            $table->foreignUuid('contact_id')->comment('Foreign key on contacts table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->uuid('worldwide_quote_version_id')->comment('Foreign key on worldwide_quote_versions table');
            $table->foreign('worldwide_quote_version_id', 'c_wqv_worldwide_quote_version_id_foreign')->references('id')->on('worldwide_quote_versions')->cascadeOnDelete()->cascadeOnUpdate();

            $table->primary(['contact_id', 'worldwide_quote_version_id'], 'contact_worldwide_quote_version_primary');
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

        Schema::dropIfExists('contact_worldwide_quote_version');

        Schema::enableForeignKeyConstraints();
    }
}
