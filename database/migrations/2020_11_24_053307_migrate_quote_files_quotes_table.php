<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MigrateQuoteFilesQuotesTable extends Migration
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
            /*
             * Migrate Distributor files.
             */
            DB::table('quote_files')
                ->select('id', 'quote_id', 'file_type')
                ->whereNull('deleted_at')
                ->cursor()
                ->each(function ($quoteFile) {
                    $qualifiedKey = $quoteFile->file_type === 'Distributor Price List' ? 'distributor_file_id' : 'schedule_file_id';

                    DB::table('quotes')->where('id', $quoteFile->quote_id)->update([$qualifiedKey => $quoteFile->id]);
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
    }
}
