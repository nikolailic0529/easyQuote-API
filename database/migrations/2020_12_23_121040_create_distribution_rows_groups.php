<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDistributionRowsGroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('distribution_rows_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('worldwide_distribution_id')->comment('Foreign key on worldwide_distributions table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('group_name', 250)->comment('Rows group name');
            $table->string('search_text', 500)->comment('Rows search text');

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

        Schema::dropIfExists('distribution_rows_groups');

        Schema::enableForeignKeyConstraints();
    }
}
