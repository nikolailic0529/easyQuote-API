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
        $seeds = DB::table('addresses')
            ->select(['addresses.id as address_id', 'addresses.contact_id'])
            ->whereNotNull('addresses.contact_id')
            ->join('contacts', 'contacts.id', 'addresses.contact_id')
            ->get();

        DB::transaction(static function () use ($seeds): void {
            foreach ($seeds as $seed) {
                DB::table('contacts')
                    ->where('id', $seed->contact_id)
                    ->update([
                        'address_id' => $seed->address_id,
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
        //
    }
};
