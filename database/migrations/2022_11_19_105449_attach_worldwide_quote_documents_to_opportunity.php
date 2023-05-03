<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
//        '4d6833e8-d018-4934-bfae-e8587f7aec51' => WorldwideQuote::class,
//        '629f4c90-cd1f-479d-b60c-af912fa5fc4a' => Opportunity::class,

        $seeds = DB::table('attachables')
            ->join('worldwide_quotes', 'worldwide_quotes.id', 'attachables.attachable_id')
            ->where('attachables.attachable_type', '4d6833e8-d018-4934-bfae-e8587f7aec51')
            ->select([
                'worldwide_quotes.opportunity_id',
                'attachables.attachment_id',
            ])
            ->get();

        DB::transaction(static function () use ($seeds): void {
            foreach ($seeds as $seed) {
                DB::table('attachables')
                    ->insertOrIgnore([
                        'attachable_id' => $seed->opportunity_id,
                        'attachable_type' => '629f4c90-cd1f-479d-b60c-af912fa5fc4a',
                        'attachment_id' => $seed->attachment_id,
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
        Schema::table('opportunity', function (Blueprint $table) {
        });
    }
};
