<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('sales_unit_id')
                ->nullable()
                ->after('team_id')
                ->comment('Foreign key to sales_units table');
        });

        DB::table('users')
            ->whereNull('sales_unit_id')
            ->update(['sales_unit_id' => '9e9ea3fc-e532-49f9-8b2b-e8bde016e149']);


        Schema::table('users', function (Blueprint $table) {
            $table->uuid('sales_unit_id')
                ->nullable(false)
                ->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('sales_unit_id')
                ->references('id')
                ->on('sales_units')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
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

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_unit_id');
        });

        Schema::enableForeignKeyConstraints();
    }
};
