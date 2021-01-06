<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateActiveVersionIdQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::beginTransaction();

        try {
            /**
             * Update appropriate active_quote_id in the quotes table
             * using values from the pivot table
             */
            DB::table('quote_version')
                ->where('is_using', true)
                ->cursor()
                ->each(function ($pivot) {
                    DB::table('quotes')->where('id', $pivot->quote_id)->update(['active_version_id' => $pivot->version_id]);
                });
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        DB::commit();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
