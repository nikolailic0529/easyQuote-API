<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDistributionRowsGroupMappedRowTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('distribution_rows_group_mapped_row', function (Blueprint $table) {
            $table->foreignUuid('mapped_row_id')->comment('Foreign key on mapped_rows table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('rows_group_id')->comment('Foreign key on distribution_rows_groups table')->constrained('distribution_rows_groups')->cascadeOnDelete()->cascadeOnUpdate();

            $table->primary(['rows_group_id', 'mapped_row_id'], 'group_mapped_row_primary');
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

        Schema::dropIfExists('distribution_rows_group_mapped_row');

        Schema::enableForeignKeyConstraints();
    }
}
