<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $con = DB::connection($this->getConnection());


        $con->transaction(static function () use ($con) {
            $con->table('tasks')
                ->lazyById(100)
                ->each(static function (stdClass $task) use ($con) {
                    $content = collect(json_decode($task->content, true));
                    $content->transform(static function (array $control): array {
                        $control['child'] = collect($control['child'])
                            ->map(static function (array $ch) {
                                if ('36e2e1ec-38cb-4d31-9b92-4808b1cca81a' === Arr::get($ch, 'id')) {
                                    $ch['controls'] = [];
                                }

                                return $ch;
                            })
                            ->all();

                        return $control;
                    });

                    $con->table('tasks')
                        ->where('id', $task->id)
                        ->update(['content' => $content->toJson()]);
                });
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
