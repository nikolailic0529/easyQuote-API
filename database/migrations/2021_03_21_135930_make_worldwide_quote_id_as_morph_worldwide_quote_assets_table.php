<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeWorldwideQuoteIdAsMorphWorldwideQuoteAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quote_assets', function (Blueprint $table) {
            $table->dropForeign(['worldwide_quote_id']);

            $table->string('worldwide_quote_type')->nullable()->after('worldwide_quote_id');

            $table->index(['worldwide_quote_type', 'worldwide_quote_id'], 'worldwide_quote_assets_worldwide_quote_type_id_index');
        });

        DB::transaction(function () {
            DB::table('worldwide_quote_assets')
                ->update(['worldwide_quote_type' => \App\Domain\Worldwide\Models\WorldwideQuote::class]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worldwide_quote_assets', function (Blueprint $table) {
            $table->dropIndex('worldwide_quote_assets_worldwide_quote_type_id_index');

            $table->dropColumn('worldwide_quote_type');

            $table->foreign('worldwide_quote_id')->references('id')->on('worldwide_quotes')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
}
