<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTeamIdUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('team_id')->nullable()->after('id')->comment('Foreign key on teams table')->constrained()->nullOnDelete()->cascadeOnUpdate();
        });

        DB::transaction(function () {

            DB::table('users')
                ->whereNull('team_id')
                ->update(['team_id' => UT_RESCUE]);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['team_id']);

            $table->dropColumn('team_id');
        });
    }
}
