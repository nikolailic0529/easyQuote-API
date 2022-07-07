<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $seeds = DB::table('tasks')
            ->where('taskable_id', '<>', '')
            ->get(['id as task_id', 'taskable_id as model_id', 'taskable_type as model_type']);

        DB::transaction(static function () use ($seeds): void {

            foreach ($seeds as $seed) {

                DB::table('model_has_tasks')
                    ->insert((array)$seed);
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
