<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTeamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('team_name')->comment('Team name');
            $table->unsignedFloat('monthly_goal_amount', 15)->nullable()->comment('Team Monthly Goal amount');

            $table->boolean('is_system')->default(false)->comment('Whether the entity is system defined');

            $table->timestamps();
            $table->softDeletes()->index();
        });

        DB::transaction(function () {

            DB::table('teams')->insertOrIgnore([
                'id' => UT_RESCUE,
                'team_name' => 'Rescue',
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::table('teams')->insertOrIgnore([
                'id' => UT_EPD_WW,
                'team_name' => 'EPD Worldwide',
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('teams');

        Schema::enableForeignKeyConstraints();
    }
}
