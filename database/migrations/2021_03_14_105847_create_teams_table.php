<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => \Database\Seeders\TeamSeeder::class]);
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
