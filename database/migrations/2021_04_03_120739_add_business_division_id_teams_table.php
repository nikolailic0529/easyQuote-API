<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBusinessDivisionIdTeamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->foreignUuid('business_division_id')->nullable()->after('id')->comment('Foreign key on business_divisions table')->constrained()->nullOnDelete()->cascadeOnUpdate();
        });

        DB::transaction(function () {
            DB::table('teams')
                ->where('id', UT_RESCUE)
                ->update(['business_division_id' => BD_RESCUE]);

            DB::table('teams')
                ->where('id', UT_EPD_WW)
                ->update(['business_division_id' => BD_WORLDWIDE]);

            DB::table('teams')
                ->whereNotIn('id', [UT_RESCUE, UT_EPD_WW])
                ->update(['business_division_id' => BD_RESCUE]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_division_id');
        });
    }
}
