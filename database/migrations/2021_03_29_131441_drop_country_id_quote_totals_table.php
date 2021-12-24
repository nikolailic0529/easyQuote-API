<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropCountryIdQuoteTotalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quote_totals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('country_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quote_totals', function (Blueprint $table) {
            $table->foreignUuid('country_id')->nullable()->after('location_id')->constrained()->nullOnDelete()->cascadeOnUpdate();
        });
    }
}
