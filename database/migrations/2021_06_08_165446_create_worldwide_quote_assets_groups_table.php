<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorldwideQuoteAssetsGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('worldwide_quote_assets_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('replicated_assets_group_id')->nullable()->index()->comment('The assets group ID the group replicated from');

            $table->foreignUuid('worldwide_quote_version_id')->comment('Foreign key on worldwide_quote_versions table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->string('group_name', 250)->comment('Assets group name');
            $table->string('search_text', 500)->comment('Assets search text');

            $table->boolean('is_selected')->default(0)->comment('Whether the group is selected');

            $table->timestamps();
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

        Schema::dropIfExists('worldwide_quote_assets_groups');

        Schema::enableForeignKeyConstraints();
    }
}
