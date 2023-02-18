<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class DeleteQuoteVersionsQuotesTable extends Migration
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
            DB::table('quotes')
                ->whereIn('id', DB::table('quote_versions')->select('id'))
                ->delete();

            DB::table('quote_discount')
                ->whereIn('quote_id', DB::table('quote_versions')->select('id'))
                ->delete();
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
    }
}
