<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $pictures = DB::table('images')
            ->where('imageable_type', '7209618c-9c1a-4b52-ba1d-933b3a433b3c')
            ->orderByDesc('created_at')
            ->get();

        $seeds = $pictures
            ->groupBy('imageable_id')
            ->map(static function (Collection $group): mixed {
                return $group->sortByDesc('created_at')->first();
            })
            ->values();

        $con = DB::connection($this->getConnection());

        $con->transaction(static function () use ($seeds): void {
            foreach ($seeds as $seed) {
                DB::table('users')
                    ->where('id', $seed->imageable_id)
                    ->update(['picture_id' => $seed->id]);
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
