<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MigrateQuoteTemplateVendorToVendorQuoteTemplateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $pivotRecords = DB::table('quote_templates')
            ->select('id as quote_template_id', 'vendor_id')
            ->get();

        $pivotRecords = $pivotRecords->map(function (object $pivot) {
            return (array) $pivot;
        });

        DB::transaction(function () use ($pivotRecords) {
            DB::table('quote_template_vendor')
                ->insert(
                    $pivotRecords->all()
                );
        });
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
