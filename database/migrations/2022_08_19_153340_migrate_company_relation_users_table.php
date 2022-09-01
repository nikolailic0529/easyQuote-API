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

        $relations = $con->table('users')
            ->whereNotNull('company_id')
            ->get(['id', 'company_id']);

        $con->transaction(static fn () => $relations->each(static function (stdClass $rel) use ($con) {
            $con
                ->table('model_has_companies')
                ->insertOrIgnore([
                    'model_id' => $rel->id,
                    'model_type' => '7209618c-9c1a-4b52-ba1d-933b3a433b3c',
                    'company_id' => $rel->company_id,
                ]);
        }));
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
