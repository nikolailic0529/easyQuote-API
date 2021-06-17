<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorldwideQuoteAssetsGroupAssetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('worldwide_quote_assets_group_asset');

        Schema::create('worldwide_quote_assets_group_asset', function (Blueprint $table) {
            $table->foreignUuid('group_id')->comment('Foreign key on worldwide_quote_assets_groups table')->constrained('worldwide_quote_assets_groups')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignUuid('asset_id')->comment('Foreign key on worldwide_quote_assets table')->constrained('worldwide_quote_assets')->cascadeOnUpdate()->cascadeOnDelete();

            $table->primary(['group_id', 'asset_id']);
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

        Schema::dropIfExists('worldwide_quote_assets_group_asset');

        Schema::enableForeignKeyConstraints();
    }
}
