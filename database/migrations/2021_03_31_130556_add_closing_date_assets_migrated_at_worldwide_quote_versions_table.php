<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClosingDateAssetsMigratedAtWorldwideQuoteVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quote_versions', function (Blueprint $table) {
            $table->date('closing_date')->nullable()->after('completeness')->comment('Quote Closing Date');
            $table->timestamp('assets_migrated_at')->nullable()->comment('Timestamp of Assets Migration');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worldwide_quote_versions', function (Blueprint $table) {
            $table->dropColumn([
                'closing_date',
                'assets_migrated_at',
            ]);
        });
    }
}
