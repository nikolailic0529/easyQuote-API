<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        //
    }
}
