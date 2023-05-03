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
        $seeds = DB::table('quote_notes')->get();

        DB::transaction(static function () use ($seeds): void {
            foreach ($seeds as $seed) {
                $flags = $seed->is_from_quote ? 1 << 0 : 0;

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
                        'model_type' => '6c0f3f29-2d00-4174-9ef8-55aa5889a812',
                        'model_id' => $seed->quote_id,
                    ]);

                if (null !== $seed->quote_version_id) {
                    DB::table('model_has_notes')
                        ->insertOrIgnore([
                            'note_id' => $seed->id,
                            'model_type' => 'f3044d32-d2fd-48bd-b5cc-b3b04160ec24',
                            'model_id' => $seed->quote_version_id,
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
