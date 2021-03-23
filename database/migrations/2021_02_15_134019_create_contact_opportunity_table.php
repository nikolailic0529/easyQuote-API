<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactOpportunityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_opportunity', function (Blueprint $table) {
            $table->foreignUuid('contact_id')->comment('Foreign key on contacts table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('opportunity_id')->comment('Foreign key on opportunities table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->primary(['opportunity_id', 'contact_id']);
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

        Schema::dropIfExists('contact_opportunity');

        Schema::enableForeignKeyConstraints();
    }
}
