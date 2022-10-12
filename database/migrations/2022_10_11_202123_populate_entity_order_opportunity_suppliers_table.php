<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
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
        $suppliers = DB::table('opportunity_suppliers')
            ->whereNull('deleted_at')
            ->orderBy('created_at')
            ->get(['id', 'opportunity_id', 'created_at'])
            ->groupBy('opportunity_id')
            ->map(static function (Collection $group): Collection {
                return $group
                    ->sortBy('created_at')
                    ->values()
                    ->each(static function (stdClass $supplier, int $i) {
                        $supplier->entity_order = $i;
                    });
            })
            ->collapse();

        DB::transaction(static function () use ($suppliers): void {
            foreach ($suppliers as $supplier) {
                DB::table('opportunity_suppliers')
                    ->where('id', $supplier->id)
                    ->update(['entity_order' => $supplier->entity_order]);
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
