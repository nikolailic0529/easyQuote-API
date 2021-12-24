<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class PopulateUserIdToAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $addressIDs = DB::table('addresses')->pluck('id');

        $addressUserMap = [];

        foreach ($addressIDs as $addressID) {
            $userID = DB::table('companies', 'c')
                ->join('addressables as a', function (JoinClause $join) use ($addressID) {
                    $join->on('a.addressable_id', 'c.id')
                        ->where('a.address_id', $addressID);
                })
                ->join('users as u', 'u.id', 'c.user_id')
                ->whereNotNull('c.user_id')
                ->orderBy('c.created_at')
                ->value('c.user_id');

            if (!is_null($userID)) {
                $addressUserMap[$addressID] = $userID;
            }
        }

        DB::transaction(function () use ($addressUserMap) {

            foreach ($addressUserMap as $addressID => $userID) {
                DB::table('addresses')
                    ->where('id', $addressID)
                    ->update(['user_id' => $userID]);
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
}
