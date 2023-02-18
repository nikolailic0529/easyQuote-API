<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class PopulateUserIdToContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $contactIDs = DB::table('contacts')->pluck('id');

        $contactUserMap = [];

        foreach ($contactIDs as $contactID) {
            $userID = DB::table('companies', 'c')
                ->join('contactables as cnt', function (JoinClause $join) use ($contactID) {
                    $join->on('cnt.contactable_id', 'c.id')
                        ->where('cnt.contact_id', $contactID);
                })
                ->join('users as u', 'u.id', 'c.user_id')
                ->whereNotNull('c.user_id')
                ->orderBy('c.created_at')
                ->value('c.user_id');

            if (!is_null($userID)) {
                $contactUserMap[$contactID] = $userID;
            }
        }

        DB::transaction(function () use ($contactUserMap) {
            foreach ($contactUserMap as $addressID => $userID) {
                DB::table('contacts')
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
    }
}
