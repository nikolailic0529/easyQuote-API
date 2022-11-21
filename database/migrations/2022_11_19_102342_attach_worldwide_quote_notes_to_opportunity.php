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
//        '4d6833e8-d018-4934-bfae-e8587f7aec51' => WorldwideQuote::class,
//        '629f4c90-cd1f-479d-b60c-af912fa5fc4a' => Opportunity::class,

        $seeds = DB::table('model_has_notes')
            ->join('worldwide_quotes', 'worldwide_quotes.id', 'model_has_notes.model_id')
            ->where('model_has_notes.model_type', '4d6833e8-d018-4934-bfae-e8587f7aec51')
            ->select([
                'worldwide_quotes.opportunity_id',
                'model_has_notes.note_id',
            ])
            ->get();

        DB::transaction(static function () use ($seeds): void {
            foreach ($seeds as $seed) {
                DB::table('model_has_notes')
                    ->insertOrIgnore([
                        'model_id' => $seed->opportunity_id,
                        'model_type' => '629f4c90-cd1f-479d-b60c-af912fa5fc4a',
                        'note_id' => $seed->note_id,
                    ]);
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
