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
        $seeds = DB::table('opportunity_notes')->get();

        DB::transaction(static function () use ($seeds): void {
            foreach ($seeds as $seed) {
                DB::table('notes')
                    ->insertOrIgnore([
                        'id' => $seed->id,
                        'user_id' => $seed->user_id,
                        'note' => $seed->text,
                        'created_at' => $seed->created_at,
                        'updated_at' => $seed->updated_at,
                        'deleted_at' => $seed->deleted_at,
                    ]);

                DB::table('model_has_notes')
                    ->insertOrIgnore([
                        'note_id' => $seed->id,
                        'model_type' => '629f4c90-cd1f-479d-b60c-af912fa5fc4a',
                        'model_id' => $seed->opportunity_id,
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
    }
};
