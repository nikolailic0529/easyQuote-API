<?php

use Database\Seeders\PipelineSeeder;
use Database\Seeders\SpaceSeeder;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPipelineIdOpportunitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->uuid('pipeline_id')->nullable()->after('user_id')->comment('Foreign key on pipelines table');
        });

        $spaceSeeds = json_decode(file_get_contents(database_path('seeders/models/spaces.json')), true);

        DB::transaction(function () use ($spaceSeeds) {

            foreach ($spaceSeeds as $seed) {

                DB::table('spaces')
                    ->insertOrIgnore([
                        'id' => $seed['id'],
                        'space_name' => $seed['space_name']
                    ]);

            }

        });

        $pipelineSeeds = json_decode(file_get_contents(database_path('seeders/models/pipelines.json')), true);

        DB::transaction(function () use ($pipelineSeeds) {

            foreach ($pipelineSeeds as $pipelineSeed) {
                DB::table('pipelines')
                    ->insertOrIgnore([
                        'id' => $pipelineSeed['id'],
                        'space_id' => $pipelineSeed['space_id'],
//                        'opportunity_form_schema_id' => $pipelineSeed['opportunity_form_schema']['id'],
                        'pipeline_name' => $pipelineSeed['pipeline_name'],
                        'is_system' => true,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                foreach ($pipelineSeed['pipeline_stages'] as $stageSeed) {

                    DB::table('pipeline_stages')
                        ->insertOrIgnore([
                            'id' => $stageSeed['id'],
                            'pipeline_id' => $pipelineSeed['id'],
                            'stage_name' => $stageSeed['stage_name'],
                            'stage_order' => $stageSeed['stage_order'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                }
            }

        });

        DB::transaction(function () {

            DB::table('opportunities')
                ->update([
                    'pipeline_id' => PL_WWDP
                ]);

        });

        Schema::table('opportunities', function (Blueprint $table) {

            $table->uuid('pipeline_id')->nullable(false)->change();

        });

        Schema::table('opportunities', function (Blueprint $table) {
            $table->foreign('pipeline_id')->references('id')->on('pipelines')->cascadeOnDelete()->cascadeOnUpdate();
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

        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pipeline_id');
        });

        Schema::enableForeignKeyConstraints();
    }
}
