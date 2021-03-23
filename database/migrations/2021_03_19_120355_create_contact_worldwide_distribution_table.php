<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactWorldwideDistributionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_worldwide_distribution', function (Blueprint $table) {
            $table->foreignUuid('contact_id')->comment('Foreign key on contacts table')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignUuid('worldwide_distribution_id')->comment('Foreign key on worldwide_distributions table')->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            $table->boolean('is_default')->default(false)->comment('Whether the contact is default for the entity');

            $table->primary(['worldwide_distribution_id', 'contact_id'], 'contact_worldwide_distribution_primary');
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

        Schema::dropIfExists('contact_worldwide_distribution');

        Schema::enableForeignKeyConstraints();
    }
}
