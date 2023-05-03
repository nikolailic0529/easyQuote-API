<?php

use App\Domain\Asset\Models\AssetCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');

            $table->timestamps();
            $table->softDeletes()->index();

            $table->unique(['name', 'deleted_at']);
        });

        $seeds = collect(json_decode(file_get_contents(database_path('seeders/models/asset_categories.json')), true));

        $seeds->each(fn ($seed) => AssetCategory::make($seed)->saveOrFail());
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('asset_categories');

        Schema::enableForeignKeyConstraints();
    }
}
