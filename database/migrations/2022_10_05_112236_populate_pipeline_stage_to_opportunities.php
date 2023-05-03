<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $opps = DB::table('opportunities')
            ->whereNull('pipeline_stage_id')
            ->select(['id', 'pipeline_id', 'sale_action_name'])
            ->get();

        $oppStageMap = collect($opps)
            ->lazy()
            ->mapWithKeys(static function (stdClass $opp): Generator {
                sscanf($opp->sale_action_name, '%d.%s', $ord, $stage);

                $stage ??= $opp->sale_action_name;

                yield $opp->id => DB::table('pipeline_stages')
                    ->where('pipeline_id', $opp->pipeline_id)
                    ->where('stage_name', $stage)
                    ->whereNull('deleted_at')
                    ->value('id');
            })
            ->filter(static function (?string $stageId): bool {
                return $stageId !== null;
            })
            ->all();

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
    }
};
