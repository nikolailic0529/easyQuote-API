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
        $opps = DB::table('opportunities')
            ->select(['id', 'pipeline_id', 'sale_action_name'])
            ->get();

        $oppStageMap = [];

        foreach ($opps as $opp) {

            sscanf($opp->sale_action_name, '%d.%s', $ord, $stage);

            $stage ??= $opp->sale_action_name;

            $oppStageMap[$opp->id] = DB::table('pipeline_stages')
                ->where('pipeline_id', $opp->pipeline_id)
                ->where('stage_name', $stage)
                ->whereNull('deleted_at')
                ->value('id');
        }

        DB::transaction(static function () use ($oppStageMap) {

            foreach ($oppStageMap as $oppId => $stageId) {

                DB::table('opportunities')
                    ->where('id', $oppId)
                    ->update(['pipeline_stage_id' => $stageId]);

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
