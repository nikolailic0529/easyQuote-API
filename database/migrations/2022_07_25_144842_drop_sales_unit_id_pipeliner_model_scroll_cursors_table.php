<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('pipeliner_model_scroll_cursors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_unit_id');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection($this->getConnection())->table('pipeliner_model_scroll_cursors')->delete();

        Schema::table('pipeliner_model_scroll_cursors', function (Blueprint $table) {
            $table->foreignUuid('sales_unit_id')
                ->after('id')
                ->comment('Foreign key to sales_units table')
                ->constrained()->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
};
