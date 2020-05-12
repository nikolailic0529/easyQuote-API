<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCountryIdQuoteLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('quote_location_totals', function (Blueprint $table) {
            $table->uuid('country_id')->nullable()->after('location_id');
            $table->foreign('country_id')->references('id')->on('countries')->onUpdate('cascade')->onDelete('set null');
        });
        
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('quote_location_totals', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropColumn('country_id');
        });

        Schema::enableForeignKeyConstraints();
    }
}
