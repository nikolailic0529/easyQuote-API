<?php

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
        $stageValueMapping = [
            'Init' => 1,
            'Import' => 30,
            'Review' => 60,
            'Results' => 99,
            'Completed' => 100,
        ];

        $con = DB::connection($this->getConnection());

        $con->transaction(static function () use ($con, $stageValueMapping) {
            foreach ($stageValueMapping as $stage => $value) {
                $con->table('data_allocations')
                    ->where('stage', $stage)
                    ->update(['stage' => $value]);
            }
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
