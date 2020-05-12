<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('place_id')->comment('Place unique id');

            $table->uuid('country_id')->nullable()->comment('Foreign key on countries table');
            $table->foreign('country_id')->references('id')->on('countries')->onUpdate('cascade')->onDelete('set null');

            $table->string('searchable_address')->nullable()->index()->comment('Searchable address string');

            $table->point('coordinates')->spatialIndex()->comment('Spatial coordinates');

            $table->string('accuracy')->nullable()->comment('Location accuracy');

            $table->char('country_code', 2)->index()->comment('Country code in iso_3166_2 format');
            $table->string('formatted_address')->index()->comment('Full formatted address of the location');

            $table->string('administrative_area_level_1')->nullable()->comment('Highest administrative area level like a country');
            $table->string('administrative_area_level_2')->nullable()->comment('Secondary administrative area level like a city');

            $table->string('route')->nullable()->comment('Route name on the map');
            $table->string('locality')->nullable()->comment('Locality');

            $table->string('premise')->nullable()->comment('Building name on the map');
            $table->string('postal_code')->nullable()->commant('Location postal code');
            $table->string('postal_code_suffix')->nullable()->commant('Location postal code suffix');

            $table->string('street_number')->nullable()->comment('Location street number');

            $table->string('postal_town')->nullable()->comment('Postal location town');

            $table->unique(['place_id', 'deleted_at']);

            $table->timestamps();
            $table->softDeletes()->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('locations');
    }
}
