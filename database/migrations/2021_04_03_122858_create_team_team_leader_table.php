<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeamTeamLeaderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_team_leader', function (Blueprint $table) {
            $table->foreignUuid('team_id')->comment('Foreign key on teams table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('team_leader_id')->comment('Foreign key on users table')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
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

        Schema::dropIfExists('team_team_leader');

        Schema::enableForeignKeyConstraints();
    }
}
