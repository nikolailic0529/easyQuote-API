<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(static function () {
            DB::table('companies')
                ->whereRaw(sprintf("flags & %d = %d", Company::SYSTEM, Company::SYSTEM))
                ->whereRaw(sprintf("flags & %d != %d", Company::SYNC_PROTECTED, Company::SYNC_PROTECTED))
                ->update([
                    'flags' => DB::raw("flags | ".Company::SYNC_PROTECTED),
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
        //
    }
};
