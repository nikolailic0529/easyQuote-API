<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddQuoteTypeAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->uuid('quote_type')->nullable()->after('quote_id')->comment('Quote entity type');
        });

        DB::transaction(function () {

            DB::table('assets')
                ->whereNotNull('quote_id')
                ->update([
                    'quote_type' => '6c0f3f29-2d00-4174-9ef8-55aa5889a812',
                ]);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('quote_type');
        });
    }
}
