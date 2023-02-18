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
        $seeds = DB::table('worldwide_quote_notes')->get();

        DB::transaction(static function () use ($seeds): void {
            foreach ($seeds as $seed) {
                $flags = $seed->is_for_submitted_quote ? 1 << 0 : 0;

                DB::table('notes')
                    ->insertOrIgnore([
                        'id' => $seed->id,
                        'user_id' => $seed->user_id,
                        'note' => $seed->text,
                        'flags' => $flags,
                        'created_at' => $seed->created_at,
                        'updated_at' => $seed->updated_at,
                        'deleted_at' => $seed->deleted_at,
                    ]);

                DB::table('model_has_notes')
                    ->insertOrIgnore([
                        'note_id' => $seed->id,
                        'model_type' => '4d6833e8-d018-4934-bfae-e8587f7aec51',
                        'model_id' => $seed->worldwide_quote_id,
                    ]);

                if (null !== $seed->worldwide_quote_version_id) {
                    DB::table('model_has_notes')
                        ->insertOrIgnore([
                            'note_id' => $seed->id,
                            'model_type' => '9d7c91c4-5308-4a40-b49e-f10ae552e480',
                            'model_id' => $seed->worldwide_quote_version_id,
                        ]);
                }
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
