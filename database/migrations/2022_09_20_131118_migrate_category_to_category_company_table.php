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
        $con = DB::connection($this->getConnection());

        $map = $con->table('company_categories')->pluck('id', 'name');

        $seeds = $con->table('companies')
            ->whereNotNull('category')
            ->get(['id', 'category'])
            ->map(static function (stdClass $seed) use ($map): array {
                return [
                    'company_id' => $seed->id,
                    'category_id' => $map[$seed->category],
                ];
            });

        $con->transaction(static function () use ($con, $seeds): void {
            foreach ($seeds as $seed) {
                $con->table('category_company')
                    ->insertOrIgnore($seed);
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
