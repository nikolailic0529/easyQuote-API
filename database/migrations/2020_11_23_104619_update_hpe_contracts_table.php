<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateHpeContractsTable extends Migration
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
            $hpeId = DB::table('vendors')->whereNull('deleted_at')->where('short_code', 'HPE')->value('id');
        
            DB::table('quote_templates')
                ->where('type', 3)
                ->whereNull('vendor_id')
                ->update(['vendor_id' => $hpeId]);
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
